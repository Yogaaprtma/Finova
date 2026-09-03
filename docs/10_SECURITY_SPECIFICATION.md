# 10 — Security Specification

> **Document Version**: 1.0  
> **Last Updated**: 2026-08-25  
> **Status**: Approved for Implementation  
> **Cross-references**: [04_SYSTEM_ARCHITECTURE](./04_SYSTEM_ARCHITECTURE.md), [07_INTEGRATION_SPECIFICATION](./07_INTEGRATION_SPECIFICATION.md)

---

## 1. Security Principles

| Principle | Application |
|-----------|-------------|
| **Defense in depth** | Multiple layers of security; no single point of failure |
| **Least privilege** | Each component has minimum necessary access |
| **Secure by default** | Security is on by default, not opt-in |
| **Never trust input** | All user input, AI output, and webhook payloads are validated |
| **Encrypt sensitive data** | API keys, credentials, and financial data at rest |
| **Audit everything** | All data changes are logged for accountability |
| **Fail closed** | On security failure, deny access rather than allow |

---

## 2. Authentication

### 2.1 Implementation

- **Framework**: Laravel Fortify (or Breeze) for session-based auth
- **Password hashing**: bcrypt with cost factor 12
- **Session driver**: Redis (server-side sessions)
- **Session lifetime**: 2 hours (extendable to 30 days with "remember me")
- **Session regeneration**: Regenerate session ID on login and privilege escalation

### 2.2 Password Requirements

| Requirement | Rule |
|-------------|------|
| Minimum length | 8 characters |
| Complexity | At least one letter and one number |
| Breached password check | `Password::defaults()` with Laravel's Uncompromised rule (V1.5) |
| Password confirmation | Required for: changing password, changing email, deleting account |

### 2.3 Rate Limiting

| Action | Limit | Lockout |
|--------|-------|---------|
| Login attempts | 5 per minute per IP | 60 seconds |
| Registration | 3 per hour per IP | 1 hour |
| Password reset | 3 per hour per email | 1 hour |
| Transaction creation | 60 per minute per user | Soft limit (warning) |
| AI parsing requests | 30 per hour per user | Reject with message |
| API webhook calls | 120 per minute per IP | 60 seconds |

### 2.4 Multi-Factor Authentication (V2)

- TOTP-based 2FA (Google Authenticator, Authy)
- Recovery codes
- Not required for V1 (single user, controlled access)

---

## 3. Authorization

### 3.1 V1 Model (Single User)

For V1, authorization is simple:
- All authenticated users can access all features
- All data is scoped to `user_id` in database queries
- Global query scopes on Eloquent models ensure user isolation

```php
// Example: Global scope on Transaction model
class Transaction extends Model
{
    protected static function booted()
    {
        static::addGlobalScope('user', function (Builder $builder) {
            if (auth()->check()) {
                $builder->where('user_id', auth()->id());
            }
        });
    }
}
```

### 3.2 V2 Model (Multi-User)

When multi-user is added:
- Laravel Policies for resource authorization
- Roles: owner, viewer (for shared accounts)
- Row-level data isolation (already enforced by user_id scoping)

---

## 4. CSRF Protection

- **Enabled globally** via Laravel's `VerifyCsrfToken` middleware
- Inertia.js automatically includes CSRF tokens in requests
- **Exceptions**: Webhook endpoints (`/api/webhooks/*`) are excluded from CSRF but protected by signature verification

```php
// app/Http/Middleware/VerifyCsrfToken.php
protected $except = [
    'api/webhooks/*',
];
```

---

## 5. Input Validation

### 5.1 Server-Side (Laravel Form Requests)

Every write operation uses a Form Request with explicit validation:

```php
class StoreTransactionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(TransactionType::values())],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'amount' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'currency' => ['required', 'string', 'size:3'],
            'description' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'category_id' => ['required_if:type,income,expense', 'uuid', 'exists:categories,id'],
            'account_id' => ['required', 'uuid', 'exists:accounts,id'],
            // ... more fields depending on type
        ];
    }
}
```

### 5.2 Client-Side (Vue + Vee-validate)

Client-side validation for UX only — server-side validation is the source of truth.

### 5.3 AI Output Validation

AI-parsed transactions go through the same Form Request validation as manual transactions. Additional checks:
- Amount must be within reasonable bounds (configurable)
- Date cannot be in the future
- Category must exist in user's category list
- Confidence score determines whether to auto-confirm

---

## 6. Data Protection

### 6.1 Encryption at Rest

| Data | Encryption | Method |
|------|-----------|--------|
| Database | Disk encryption on VPS | OS-level (LUKS or provider-managed) |
| User passwords | Hashed | bcrypt (cost 12) |
| Integration API keys | Encrypted in DB | `Crypt::encryptString()` (AES-256-CBC) |
| Session data | Encrypted | Laravel's encrypted session cookies |
| Backup files | Encrypted | gpg or openssl before upload |

### 6.2 Encryption in Transit

- **HTTPS enforced**: All traffic over TLS 1.2+
- **HSTS header**: `Strict-Transport-Security: max-age=31536000; includeSubDomains`
- **Redirect HTTP → HTTPS**: Nginx/Apache config or Laravel's `ForceHttps` middleware

### 6.3 Sensitive Data Handling

**Never log**:
- Passwords (even hashed)
- API keys / secrets
- Full credit card numbers
- Session tokens

**Never expose in API/Inertia responses**:
- API credentials
- Internal IDs that shouldn't be visible
- Other users' data

```php
// Model hidden attributes
class User extends Model
{
    protected $hidden = [
        'password',
        'remember_token',
        'whatsapp_number', // Only show in profile settings
    ];
}
```

---

## 7. Webhook Security

### 7.1 WhatsApp Webhook Verification

```php
class VerifyWhatsAppSignature
{
    public function handle(Request $request, Closure $next)
    {
        $provider = app(MessagingProviderInterface::class);
        
        if (!$provider->verifyWebhook($request)) {
            Log::warning('Invalid webhook signature', [
                'ip' => $request->ip(),
                'provider' => config('finance.messaging.default_provider'),
            ]);
            abort(401, 'Invalid webhook signature');
        }
        
        return $next($request);
    }
}
```

### 7.2 Webhook Best Practices

1. **Signature verification**: Every webhook request must be verified against provider's signature
2. **Idempotency**: Check `message_id` against `webhook_events` table before processing
3. **Quick response**: Return 200 OK within 3-5 seconds; process async via queue
4. **Rate limiting**: Max 120 requests/minute per IP
5. **IP allowlisting** (optional): Restrict webhook endpoint to provider's IP ranges
6. **Payload validation**: Validate webhook payload structure before processing
7. **Logging**: Log all webhook events (payload stored as JSONB) for debugging

---

## 8. API Security

### 8.1 Internal API (Inertia)

No separate API security needed — Inertia uses the same session-based auth as web pages.

### 8.2 Webhook API Routes

```php
// routes/api.php
Route::prefix('webhooks')->group(function () {
    Route::post('/whatsapp', [WhatsAppWebhookController::class, 'handle'])
        ->middleware(['throttle:webhook', 'verify.whatsapp']);
});
```

### 8.3 Future Public API (V2+)

If a public API is added:
- Laravel Sanctum for token-based auth
- API tokens with scoped permissions
- Rate limiting per token
- Request signing for sensitive operations

---

## 9. Secret Management

### 9.1 Environment Variables

```env
# .env (NEVER commit to version control)

# Application
APP_KEY=base64:...  # Laravel encryption key (auto-generated)
APP_DEBUG=false      # MUST be false in production

# Database
DB_PASSWORD=...

# Redis
REDIS_PASSWORD=...

# AI Provider
GEMINI_API_KEY=...
OPENAI_API_KEY=...

# WhatsApp Provider
FONNTE_API_KEY=...
FONNTE_WEBHOOK_TOKEN=...

# Indodax (V2)
INDODAX_API_KEY=...
INDODAX_SECRET_KEY=...
```

### 9.2 Rules

1. `.env` is in `.gitignore` — never committed
2. `.env.example` contains all keys with empty values
3. Production secrets managed via VPS environment or secrets manager
4. Rotate API keys quarterly
5. Use different API keys for development and production
6. `APP_DEBUG=false` in production (prevents error stack traces)

---

## 10. Audit Logging

### 10.1 What to Log

| Event | Data Logged |
|-------|------------|
| User login | IP, user agent, timestamp |
| User logout | Timestamp |
| Failed login | Email attempted, IP, timestamp |
| Transaction created | Transaction data, source (manual/AI/WhatsApp) |
| Transaction updated | Old values, new values |
| Transaction deleted | Transaction data (before deletion) |
| Account created/updated/deleted | Account data changes |
| Asset transaction | Purchase/sale details |
| Liability change | Payment/increase details |
| Category change | Category data changes |
| Settings change | Old/new settings values |
| AI parsing event | Raw message, parsed output, confidence, status |
| Webhook received | Provider, event type, timestamp |
| Integration error | Provider, error message, context |

### 10.2 Implementation

```php
// Audit trait for models
trait Auditable
{
    protected static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'create',
                'entity_type' => $model->getTable(),
                'entity_id' => $model->id,
                'new_values' => $model->toArray(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
        
        static::updated(function (Model $model) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'update',
                'entity_type' => $model->getTable(),
                'entity_id' => $model->id,
                'old_values' => $model->getOriginal(),
                'new_values' => $model->getChanges(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        });
        
        // ... similar for deleted
    }
}
```

### 10.3 Audit Log Retention

- Keep audit logs for 1 year
- Archive older logs to cold storage or delete
- Scheduled job to clean up logs older than retention period

---

## 11. Security Headers

```php
// Middleware or nginx config
return $next($request)
    ->header('X-Content-Type-Options', 'nosniff')
    ->header('X-Frame-Options', 'DENY')
    ->header('X-XSS-Protection', '0')  // Deprecated; use CSP instead
    ->header('Referrer-Policy', 'strict-origin-when-cross-origin')
    ->header('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
    ->header('Content-Security-Policy', "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline' fonts.googleapis.com; font-src 'self' fonts.gstatic.com; img-src 'self' data:; connect-src 'self'");
```

---

## 12. Financial Data Integrity

### 12.1 Database Transaction Isolation

All financial write operations use database transactions with appropriate isolation:

```php
DB::transaction(function () use ($data) {
    $transaction = Transaction::create($data);
    TransactionEntry::create([/* debit */]);
    TransactionEntry::create([/* credit */]);
    $this->updateAccountBalance($data->accountId);
}, 3); // Retry up to 3 times on deadlock
```

### 12.2 Balance Verification

A scheduled command runs daily to verify all cached balances match calculated balances:

```php
// app/Console/Commands/VerifyBalances.php
// Compares accounts.current_balance vs SUM of transaction_entries
// Logs discrepancies and alerts admin
```

### 12.3 Immutability Considerations

- Transaction entries are never updated in place — the service reverses old entries and creates new ones
- Audit logs are append-only — never updated or deleted (except by retention policy)
- Net worth snapshots are append-only

---

## 13. Dependency Security

### 13.1 PHP Dependencies

```bash
# Check for known vulnerabilities
composer audit

# Keep dependencies updated
composer update --with-dependencies
```

### 13.2 JavaScript Dependencies

```bash
# Check for known vulnerabilities
npm audit

# Fix vulnerabilities
npm audit fix
```

### 13.3 Dependency Policy

- Run `composer audit` and `npm audit` before every deployment
- Update dependencies monthly
- Pin major versions to avoid breaking changes
- Review changelogs for security-related updates

---

## 14. Backup Security

- Database backups are encrypted before storage
- Backup files are stored on a separate server/location (not on the same VPS)
- Backup access requires separate credentials
- Test backup restoration monthly
- See [11_DEPLOYMENT_SPECIFICATION](./11_DEPLOYMENT_SPECIFICATION.md) for backup implementation

---

## 15. Security Checklist (Pre-Deployment)

- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] HTTPS enforced with valid certificate
- [ ] HSTS header enabled
- [ ] CSRF protection active (except webhooks)
- [ ] Webhook signature verification active
- [ ] Rate limiting configured
- [ ] API keys stored in `.env`, not in code
- [ ] `.env` not accessible via web server
- [ ] Database password is strong and unique
- [ ] Redis password is set
- [ ] Security headers configured
- [ ] `composer audit` clean
- [ ] `npm audit` clean
- [ ] Error pages don't expose stack traces
- [ ] File permissions correct (storage writable, nothing else)
- [ ] Scheduled balance verification running
- [ ] Audit logging active
- [ ] Backup configured and tested
