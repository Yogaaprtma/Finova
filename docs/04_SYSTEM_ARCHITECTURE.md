# 04 — System Architecture

> **Document Version**: 1.0  
> **Last Updated**: 2026-08-25  
> **Status**: Approved for Implementation  
> **Cross-references**: [05_DATABASE_SCHEMA](./05_DATABASE_SCHEMA.md), [07_INTEGRATION_SPECIFICATION](./07_INTEGRATION_SPECIFICATION.md), [10_SECURITY_SPECIFICATION](./10_SECURITY_SPECIFICATION.md), [11_DEPLOYMENT_SPECIFICATION](./11_DEPLOYMENT_SPECIFICATION.md)

---

## 1. Technology Stack

### Core Stack

| Layer | Technology | Version | Justification |
|-------|-----------|---------|---------------|
| **Runtime** | PHP | 8.3+ | Latest stable; typed properties, enums, fibers |
| **Framework** | Laravel | 11+ | Full-stack framework; queues, scheduler, auth, migrations |
| **Frontend Bridge** | Inertia.js | 2.x | SPA-like experience without API boilerplate; shares auth/session with backend |
| **Frontend** | Vue 3 | 3.5+ | Composition API; TypeScript support; reactive; large ecosystem |
| **Language** | TypeScript | 5.x | Type safety for financial calculations on frontend |
| **CSS** | Tailwind CSS | 4.x | Rapid development; utility-first; good Laravel/Vue integration |
| **Database** | PostgreSQL | 16+ | DECIMAL precision, JSONB, interval types, row-level security |
| **Cache / Queue Broker** | Redis | 7+ | Fast cache; reliable queue driver for Laravel |
| **Task Queue** | Laravel Queue (Redis) | — | Background jobs: webhooks, AI parsing, price fetching |
| **Scheduler** | Laravel Scheduler | — | Recurring transactions, price updates, cleanup |
| **Build Tool** | Vite | 6.x | Laravel's default bundler; fast HMR; PWA plugin |

### Supporting Libraries

| Purpose | Library | Notes |
|---------|---------|-------|
| PWA | `vite-plugin-pwa` | Service worker generation, manifest |
| Charts | `Chart.js` + `vue-chartjs` | Lightweight, canvas-based, good mobile performance |
| Icons | `Heroicons` or `Lucide` | Clean, modern icon sets |
| Date Handling | `Carbon` (PHP) / `dayjs` (JS) | Timezone-aware date manipulation |
| Money (PHP) | `brick/money` or custom integer wrapper | Integer-based monetary arithmetic |
| HTTP Client (PHP) | Laravel HTTP Client (Guzzle) | For external API calls |
| Form Validation | Laravel Form Requests + Vee-validate (Vue) | Server + client validation |
| Testing | PHPUnit + Pest | Laravel testing suite |

---

## 2. Application Architecture

### 2.1 High-Level Architecture

```
┌─────────────────────────────────────────────────────────┐
│                      CLIENT (Browser/PWA)                │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌─────────┐ │
│  │ Vue 3    │  │ Inertia  │  │ Charts   │  │ PWA/SW  │ │
│  │ Components│  │ Router   │  │ (Chart.js)│  │         │ │
│  └──────────┘  └──────────┘  └──────────┘  └─────────┘ │
└────────────────────────┬────────────────────────────────┘
                         │ HTTPS (Inertia Protocol)
┌────────────────────────┴────────────────────────────────┐
│                    LARAVEL APPLICATION                    │
│                                                          │
│  ┌─────────────────────────────────────────────────────┐ │
│  │                   HTTP Layer                         │ │
│  │  Routes → Middleware → Controllers → Form Requests  │ │
│  └──────────────────────┬──────────────────────────────┘ │
│                         │                                │
│  ┌──────────────────────┴──────────────────────────────┐ │
│  │                  Service Layer                       │ │
│  │  TransactionService │ AccountService │ AssetService  │ │
│  │  LiabilityService   │ ReportService  │ CategorySvc   │ │
│  └──────────────────────┬──────────────────────────────┘ │
│                         │                                │
│  ┌──────────────────────┴──────────────────────────────┐ │
│  │                  Domain Layer                        │ │
│  │  Models │ Value Objects │ Enums │ Financial Rules    │ │
│  └──────────────────────┬──────────────────────────────┘ │
│                         │                                │
│  ┌──────────────────────┴──────────────────────────────┐ │
│  │              Infrastructure Layer                    │ │
│  │  Repositories │ Adapters │ Queue Jobs │ Integrations │ │
│  └─────────────────────────────────────────────────────┘ │
│                                                          │
│  ┌────────────┐  ┌────────────┐  ┌────────────────────┐ │
│  │ PostgreSQL │  │   Redis    │  │  External APIs     │ │
│  │ (primary)  │  │ (cache/q)  │  │  (AI, WhatsApp,    │ │
│  │            │  │            │  │   market prices)   │ │
│  └────────────┘  └────────────┘  └────────────────────┘ │
└──────────────────────────────────────────────────────────┘
```

### 2.2 Layer Responsibilities

#### HTTP Layer
- Route definitions (web routes for Inertia, webhook routes)
- Middleware (auth, CSRF, rate limiting, webhook verification)
- Controllers (thin — delegate to services)
- Form Requests (validation)
- Inertia responses (return page components with props)

#### Service Layer
- Business logic orchestration
- Transaction creation with double-entry bookkeeping
- Balance calculations
- Report generation
- Cross-cutting concerns (events, notifications)

#### Domain Layer
- Eloquent Models with relationships
- Value Objects (Money, AssetQuantity)
- Enums (TransactionType, AccountType, AssetType, etc.)
- Financial business rules (validation, constraints)

#### Infrastructure Layer
- Database queries (complex queries, reporting queries)
- External API adapters (AI, WhatsApp, market prices)
- Queue jobs (async processing)
- Cache management

---

## 3. Key Architectural Decisions

### 3.1 Simplified Double-Entry Model

**Decision**: Every transaction creates two "entries" (journal lines) — a debit and a credit.

**Rationale**: 
- Naturally handles transfers without special-case logic
- Asset purchases/sales are just entries between account types
- Balance consistency is enforced by the model itself
- Simpler than writing ad-hoc balance update logic for every transaction type

**Implementation**:
```
Transaction (header)
├── TransactionEntry (debit side)
│   └── account_id, amount (positive), entry_type: 'debit'
└── TransactionEntry (credit side)
    └── account_id, amount (positive), entry_type: 'credit'
```

**Rule**: Sum of debits MUST equal sum of credits for every transaction.

**Account Balance**: `SUM(credits) - SUM(debits)` for asset/cash accounts, `SUM(debits) - SUM(credits)` for liability accounts.

### 3.2 Inertia.js (Monolith) vs. Separate SPA + API

**Decision**: Use Inertia.js (monolith).

**Rationale**:
- No need to build and maintain a separate API
- Authentication is handled by Laravel sessions (no JWT complexity)
- CSRF protection works out of the box
- Faster development for a solo developer
- SPA-like experience without SPA infrastructure
- If API is needed later (for WhatsApp bot, mobile app), it can be added alongside Inertia

**Trade-off**: WhatsApp webhook will need a few dedicated API routes (not Inertia pages), which is fine — Laravel supports both.

### 3.3 Integer Money vs. Decimal Money

**Decision**: Store IDR amounts as `BIGINT` (integer in the smallest unit = 1 Rupiah).

**Rationale**:
- IDR has no practical subunit (sen is not used)
- Integer arithmetic eliminates rounding errors entirely
- `BIGINT` supports values up to 9,223,372,036,854,775,807 (more than enough for any personal finance amount in Rupiah)
- Display formatting (thousand separators, "Rp" prefix) is a presentation concern

**For other currencies (V2)**: Use `BIGINT` with a `decimal_places` field per currency (e.g., USD = 2, JPY = 0, BTC = 8).

### 3.4 PostgreSQL Choice

**Decision**: PostgreSQL 16+

**Rationale**:
- `NUMERIC(20,n)` for precise decimal fields (asset quantities, prices)
- `JSONB` for flexible metadata (AI parsing logs, provider responses)
- `BIGINT` for money
- Advanced date/time handling (`INTERVAL`, timezone-aware timestamps)
- Row-level security for future multi-user
- Excellent with Laravel (first-class Eloquent support)
- CTEs and window functions for reporting queries

### 3.5 Queue Architecture

**Decision**: Redis-backed Laravel Queue with dedicated worker process.

**Rationale**:
- WhatsApp webhook processing must be async (respond quickly to webhook)
- AI parsing can be slow (API call to OpenAI/Gemini)
- Market price fetching should be background
- Redis is already needed for caching

**Queue Channels**:
| Queue | Purpose | Priority |
|-------|---------|----------|
| `default` | General jobs | Normal |
| `webhooks` | WhatsApp webhook processing | High |
| `ai` | AI parsing jobs | Normal |
| `prices` | Market price updates | Low |

---

## 4. Data Flow Diagrams

### 4.1 Standard Transaction Flow (Web UI)

```
User (Browser)
  │
  ├─ 1. Fill transaction form
  │
  ├─ 2. POST /transactions (Inertia)
  │     │
  │     ├─ 3. FormRequest validates input
  │     │
  │     ├─ 4. TransactionController delegates to TransactionService
  │     │
  │     ├─ 5. TransactionService (within DB transaction):
  │     │     ├─ Creates Transaction header
  │     │     ├─ Creates TransactionEntry (debit)
  │     │     ├─ Creates TransactionEntry (credit)
  │     │     ├─ Updates account balances (cached)
  │     │     └─ Dispatches TransactionCreated event
  │     │
  │     └─ 6. Returns Inertia redirect to transactions list
  │
  └─ 7. UI updates with new transaction
```

### 4.2 WhatsApp Transaction Flow (V1.5)

```
User (WhatsApp)
  │
  ├─ 1. Sends message: "beli kopi 18rb"
  │
  ├─ 2. WhatsApp Provider → Webhook POST /api/webhooks/whatsapp
  │     │
  │     ├─ 3. Verify webhook signature
  │     │
  │     ├─ 4. Check idempotency (message ID)
  │     │
  │     ├─ 5. Dispatch ProcessWhatsAppMessage job to queue
  │     │
  │     └─ 6. Return 200 OK (within 5 seconds)
  │
  ├─ 7. Queue Worker picks up job:
  │     │
  │     ├─ 8. AiParserService.parse(message)
  │     │     ├─ Call AI API (OpenAI/Gemini)
  │     │     └─ Return structured ParsedTransaction
  │     │
  │     ├─ 9. Validate parsed data
  │     │
  │     ├─ 10. Check confidence score
  │     │     ├─ HIGH (>0.85): Auto-create transaction
  │     │     ├─ MEDIUM (0.5-0.85): Ask for confirmation via WhatsApp
  │     │     └─ LOW (<0.5): Ask user to rephrase
  │     │
  │     ├─ 11. If confirmed: TransactionService.create()
  │     │
  │     └─ 12. Send confirmation/question via WhatsApp API
  │
  └─ 13. User receives confirmation in WhatsApp
```

### 4.3 Dashboard Data Flow

```
User (Browser)
  │
  ├─ 1. GET /dashboard (Inertia)
  │     │
  │     ├─ 2. DashboardController calls multiple services:
  │     │     ├─ AccountService.getTotalCash()
  │     │     ├─ AssetService.getTotalAssetValue()
  │     │     ├─ LiabilityService.getTotalLiabilities()
  │     │     ├─ TransactionService.getMonthlyCashFlow()
  │     │     ├─ TransactionService.getRecentTransactions(10)
  │     │     └─ AssetService.getPortfolioAllocation()
  │     │
  │     ├─ 3. Cache expensive calculations (5 min TTL)
  │     │
  │     └─ 4. Return Inertia page with all data as props
  │
  └─ 5. Vue component renders dashboard with charts
```

---

## 5. Directory Structure

```
finance/
├── app/
│   ├── Console/
│   │   └── Commands/           # Artisan commands
│   ├── Enums/                  # PHP enums (TransactionType, AccountType, etc.)
│   ├── Events/                 # Domain events (TransactionCreated, etc.)
│   ├── Exceptions/             # Custom exceptions
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── DashboardController.php
│   │   │   ├── TransactionController.php
│   │   │   ├── AccountController.php
│   │   │   ├── AssetController.php
│   │   │   ├── LiabilityController.php
│   │   │   ├── CategoryController.php
│   │   │   ├── ReportController.php
│   │   │   └── Webhook/
│   │   │       └── WhatsAppWebhookController.php
│   │   ├── Middleware/
│   │   │   └── VerifyWhatsAppSignature.php
│   │   └── Requests/           # Form Request validation
│   ├── Jobs/                   # Queue jobs
│   │   ├── ProcessWhatsAppMessage.php
│   │   └── FetchMarketPrices.php
│   ├── Listeners/              # Event listeners
│   ├── Models/                 # Eloquent models
│   │   ├── User.php
│   │   ├── Account.php
│   │   ├── Transaction.php
│   │   ├── TransactionEntry.php
│   │   ├── Category.php
│   │   ├── AssetAccount.php
│   │   ├── AssetTransaction.php
│   │   ├── Liability.php
│   │   ├── LiabilityTransaction.php
│   │   └── AiParsingLog.php
│   ├── Services/               # Business logic
│   │   ├── TransactionService.php
│   │   ├── AccountService.php
│   │   ├── AssetService.php
│   │   ├── LiabilityService.php
│   │   ├── CategoryService.php
│   │   ├── ReportService.php
│   │   ├── DashboardService.php
│   │   └── Ai/
│   │       ├── AiParserService.php
│   │       └── Contracts/
│   │           └── AiProviderInterface.php
│   ├── Integrations/           # External system adapters
│   │   ├── Contracts/
│   │   │   ├── MessagingProviderInterface.php
│   │   │   └── MarketDataProviderInterface.php
│   │   ├── WhatsApp/
│   │   │   ├── FonnteAdapter.php
│   │   │   └── MetaCloudApiAdapter.php
│   │   ├── MarketData/
│   │   │   ├── IndodaxAdapter.php
│   │   │   └── ManualPriceAdapter.php
│   │   └── Ai/
│   │       ├── OpenAiAdapter.php
│   │       └── GeminiAdapter.php
│   └── ValueObjects/           # Immutable value objects
│       ├── Money.php
│       └── AssetQuantity.php
├── config/
│   └── finance.php             # App-specific config (thresholds, defaults)
├── database/
│   ├── migrations/
│   ├── seeders/
│   │   └── DefaultCategorySeeder.php
│   └── factories/
├── resources/
│   ├── js/
│   │   ├── app.ts              # Vue app entry
│   │   ├── types/              # TypeScript type definitions
│   │   │   ├── models.ts
│   │   │   └── enums.ts
│   │   ├── Components/         # Reusable Vue components
│   │   │   ├── Layout/
│   │   │   │   ├── AppLayout.vue
│   │   │   │   ├── BottomNav.vue
│   │   │   │   ├── Sidebar.vue
│   │   │   │   └── TopBar.vue
│   │   │   ├── Dashboard/
│   │   │   ├── Transaction/
│   │   │   ├── Account/
│   │   │   ├── Asset/
│   │   │   ├── Liability/
│   │   │   ├── Chart/
│   │   │   └── Shared/
│   │   │       ├── MoneyDisplay.vue
│   │   │       ├── MoneyInput.vue
│   │   │       ├── SearchBar.vue
│   │   │       └── EmptyState.vue
│   │   ├── Pages/              # Inertia pages
│   │   │   ├── Dashboard.vue
│   │   │   ├── Transaction/
│   │   │   │   ├── Index.vue
│   │   │   │   ├── Create.vue
│   │   │   │   ├── Edit.vue
│   │   │   │   └── Show.vue
│   │   │   ├── Account/
│   │   │   ├── Asset/
│   │   │   ├── Liability/
│   │   │   ├── Report/
│   │   │   ├── Category/
│   │   │   ├── Settings/
│   │   │   └── Auth/
│   │   ├── Composables/        # Vue composables
│   │   │   ├── useMoney.ts
│   │   │   ├── useTheme.ts
│   │   │   └── useFilters.ts
│   │   └── Utils/
│   │       ├── money.ts        # Money formatting utilities
│   │       └── date.ts         # Date formatting utilities
│   ├── css/
│   │   └── app.css             # Tailwind imports + custom styles
│   └── views/
│       └── app.blade.php       # Inertia root template
├── routes/
│   ├── web.php                 # Inertia routes
│   └── api.php                 # Webhook routes
├── tests/
│   ├── Feature/
│   ├── Unit/
│   └── Fixtures/
├── docs/                       # This documentation
├── public/
│   ├── icons/                  # PWA icons
│   └── manifest.webmanifest    # PWA manifest (generated by vite-plugin-pwa)
├── .env.example
├── composer.json
├── package.json
├── tsconfig.json
├── vite.config.ts
└── tailwind.config.ts
```

---

## 6. Service Layer Design

### 6.1 TransactionService

The most critical service. Responsible for creating, updating, and deleting transactions with full double-entry integrity.

```php
class TransactionService
{
    // Core operations (all wrapped in DB transactions)
    public function createIncome(CreateIncomeData $data): Transaction;
    public function createExpense(CreateExpenseData $data): Transaction;
    public function createTransfer(CreateTransferData $data): Transaction;
    public function createAssetPurchase(CreateAssetPurchaseData $data): Transaction;
    public function createAssetSale(CreateAssetSaleData $data): Transaction;
    public function createLiabilityPayment(CreateLiabilityPaymentData $data): Transaction;
    public function createAdjustment(CreateAdjustmentData $data): Transaction;
    
    public function update(Transaction $transaction, UpdateTransactionData $data): Transaction;
    public function delete(Transaction $transaction): void;
    
    // Query operations
    public function getFiltered(TransactionFilters $filters): LengthAwarePaginator;
    public function getMonthlyCashFlow(int $year, int $month): CashFlowSummary;
    public function getRecentTransactions(int $limit = 10): Collection;
}
```

### 6.2 Service Interaction Rules

1. **Controllers** call **Services** — never call repositories or models directly for write operations.
2. **Services** may call other **Services** if needed (e.g., TransactionService calls AccountService to update balance).
3. **Services** dispatch **Events** after successful operations.
4. **Queue Jobs** call **Services** (e.g., ProcessWhatsAppMessage calls TransactionService).
5. **Models** contain relationships, scopes, and accessors — no business logic.

---

## 7. Caching Strategy

| Data | Cache Key Pattern | TTL | Invalidation |
|------|------------------|-----|-------------|
| Dashboard net worth | `dashboard:networth:{user_id}` | 5 min | On any transaction/account change |
| Dashboard cash flow | `dashboard:cashflow:{user_id}:{year}:{month}` | 5 min | On income/expense transaction change |
| Account balances | `account:balance:{account_id}` | 5 min | On transaction affecting account |
| Asset total value | `assets:total:{user_id}` | 15 min | On asset transaction or price update |
| Categories list | `categories:{user_id}` | 1 hour | On category CRUD |
| Report data | `report:{type}:{user_id}:{params_hash}` | 30 min | On relevant transaction change |

**Cache invalidation strategy**: Event-driven. `TransactionCreated`, `TransactionUpdated`, `TransactionDeleted` events trigger cache flush for affected keys.

---

## 8. Error Handling Strategy

### Application Errors
- All financial operations wrapped in database transactions
- On failure: rollback + return error message to user
- Log errors with context (user, action, input data)

### External API Errors
- AI parsing failure: Fall back to manual entry prompt
- WhatsApp API failure: Queue retry with exponential backoff (max 3 retries)
- Market price API failure: Use last known price, show "stale" indicator

### Validation Errors
- Form Requests handle input validation
- Service layer validates business rules (e.g., sufficient balance for transfer)
- Return user-friendly error messages

### Financial Integrity Errors
- If double-entry validation fails (debits ≠ credits): Throw exception, do NOT save
- If balance goes negative on a non-credit account: Warning (not hard block — user may legitimately have overdraft)
- Periodic balance reconciliation check (scheduled command)

---

## 9. Event System

| Event | Dispatched By | Listeners |
|-------|--------------|-----------|
| `TransactionCreated` | TransactionService | InvalidateCache, WriteAuditLog |
| `TransactionUpdated` | TransactionService | InvalidateCache, WriteAuditLog |
| `TransactionDeleted` | TransactionService | InvalidateCache, WriteAuditLog |
| `AccountCreated` | AccountController | WriteAuditLog |
| `AccountUpdated` | AccountController | InvalidateCache, WriteAuditLog |
| `AssetPriceUpdated` | FetchMarketPrices job | InvalidateCache |
| `WhatsAppMessageReceived` | WhatsAppWebhookController | LogWebhookEvent |
| `AiParsingCompleted` | ProcessWhatsAppMessage | LogAiParsing |

---

## 10. Timezone Handling

- **Database**: All timestamps stored in UTC
- **Application**: `APP_TIMEZONE=UTC` in .env
- **User Display**: Convert to user's timezone (default `Asia/Jakarta` / UTC+7 for V1)
- **Transaction Dates**: Store as `DATE` (not `TIMESTAMP`) — the user records the date of the transaction, not the exact second
- **Created/Updated timestamps**: Store as `TIMESTAMP WITH TIME ZONE`
