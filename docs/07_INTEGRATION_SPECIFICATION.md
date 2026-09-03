# 07 — Integration Specification

> **Document Version**: 1.0  
> **Last Updated**: 2026-08-25  
> **Status**: Approved for Implementation  
> **Cross-references**: [04_SYSTEM_ARCHITECTURE](./04_SYSTEM_ARCHITECTURE.md), [06_AI_SPECIFICATION](./06_AI_SPECIFICATION.md), [10_SECURITY_SPECIFICATION](./10_SECURITY_SPECIFICATION.md)

---

## 1. Integration Philosophy

All external integrations follow these principles:

1. **Adapter pattern**: Every external service is accessed through an interface/contract
2. **Core independence**: The financial core works without any integration
3. **Replaceable providers**: Switching providers requires only a new adapter + config change
4. **Graceful degradation**: Integration failure shows a warning, not a crash
5. **Async where possible**: External API calls happen in queue jobs, not during user requests
6. **Idempotent**: Webhook handlers and import processes handle duplicates gracefully

---

## 2. Integration Map

```
┌─────────────────────────────────────────────────────┐
│                  CORE APPLICATION                    │
│                                                      │
│  TransactionService │ AssetService │ AccountService   │
│                                                      │
├──────────┬──────────┬───────────┬───────────────────┤
│          │          │           │                    │
│  ┌───────┴──┐ ┌─────┴───┐ ┌────┴────┐ ┌───────────┐│
│  │Messaging │ │   AI    │ │ Market  │ │  Import   ││
│  │Provider  │ │Provider │ │ Data    │ │ Provider  ││
│  │Interface │ │Interface│ │Provider │ │ Interface ││
│  │          │ │         │ │Interface│ │           ││
│  └────┬─────┘ └────┬────┘ └────┬────┘ └─────┬─────┘│
│       │            │           │             │      │
└───────┼────────────┼───────────┼─────────────┼──────┘
        │            │           │             │
   ┌────┴────┐  ┌────┴────┐ ┌───┴──────┐ ┌────┴────┐
   │WhatsApp │  │ Gemini  │ │CoinGecko │ │  CSV    │
   │Provider │  │  API    │ │  API     │ │ Parser  │
   │(Fonnte/ │  ├─────────┤ ├──────────┤ └─────────┘
   │ Meta)   │  │ OpenAI  │ │ Indodax  │
   └─────────┘  │  API    │ │  API     │
                └─────────┘ ├──────────┤
                            │ Yahoo    │
                            │ Finance  │
                            └──────────┘
```

---

## 3. WhatsApp Integration (V1.5)

### 3.1 Provider Analysis

| Provider | Type | Cost | Reliability | Ease of Setup | Recommendation |
|----------|------|------|-------------|---------------|----------------|
| **Meta WhatsApp Cloud API** | Official | Free (1,000 conversations/mo), then ~$0.05/conversation | High | Medium (requires Facebook Business verification) | V2 (production-grade) |
| **Fonnte** | Third-party (Indonesian) | ~Rp 100,000-250,000/month | Medium-High | Easy (no business verification) | **V1.5 (recommended for MVP)** |
| **Twilio** | Third-party (global) | ~$0.005/message | High | Medium | Alternative |
| **WATI** | Third-party | ~$49/month | High | Easy | Alternative |

### 3.2 Recommended Approach

**V1.5**: Fonnte (Indonesian provider, quick setup, affordable)  
**V2**: Meta WhatsApp Cloud API (official, free tier, more reliable)

### 3.3 WhatsApp Provider Interface

```php
interface MessagingProviderInterface
{
    /**
     * Send a text message to a phone number.
     */
    public function sendMessage(string $phoneNumber, string $message): SendResult;
    
    /**
     * Send a message with quick reply buttons.
     */
    public function sendWithButtons(
        string $phoneNumber, 
        string $message, 
        array $buttons
    ): SendResult;
    
    /**
     * Verify a webhook signature/token.
     */
    public function verifyWebhook(Request $request): bool;
    
    /**
     * Parse incoming webhook payload into a standard message object.
     */
    public function parseWebhook(Request $request): IncomingMessage;
}
```

### 3.4 IncomingMessage Value Object

```php
class IncomingMessage
{
    public function __construct(
        public readonly string $messageId,      // Provider's unique message ID
        public readonly string $phoneNumber,     // Sender's phone number (E.164)
        public readonly string $text,            // Message text content
        public readonly string $timestamp,       // ISO 8601 timestamp
        public readonly string $provider,        // 'fonnte', 'meta', etc.
        public readonly array $rawPayload,       // Original webhook payload
    ) {}
}
```

### 3.5 Webhook Processing Flow

```
POST /api/webhooks/whatsapp
│
├─ 1. VerifyWhatsAppSignature middleware
│     └─ Calls MessagingProvider::verifyWebhook()
│
├─ 2. WhatsAppWebhookController
│     ├─ Parse webhook → IncomingMessage
│     ├─ Check idempotency (message_id in webhook_events)
│     ├─ Store in webhook_events table
│     ├─ Dispatch ProcessWhatsAppMessage job
│     └─ Return 200 OK (within 3 seconds)
│
├─ 3. ProcessWhatsAppMessage job (async)
│     ├─ Look up user by phone number
│     ├─ If no user found → send "Not registered" response
│     ├─ Call AiParserService::parse()
│     ├─ Route by confidence (see AI Specification)
│     ├─ Create transaction if appropriate
│     └─ Send response via MessagingProvider::sendMessage()
│
└─ 4. User receives confirmation in WhatsApp
```

### 3.6 WhatsApp Security

- Webhook endpoint requires valid signature verification
- Rate limit: 30 messages per user per hour
- Phone number must be verified and linked to a user account
- Messages are logged but original content is not stored after parsing (privacy)
- Admin can disable WhatsApp integration per-user

---

## 4. AI Provider Integration (V1.5)

Covered in detail in [06_AI_SPECIFICATION.md](./06_AI_SPECIFICATION.md).

### 4.1 Provider Configuration

```php
// config/finance.php
'ai' => [
    'default_provider' => env('AI_PROVIDER', 'gemini'),
    'providers' => [
        'gemini' => [
            'adapter' => \App\Integrations\Ai\GeminiAdapter::class,
            'api_key' => env('GEMINI_API_KEY'),
            'model' => env('GEMINI_MODEL', 'gemini-2.0-flash'),
            'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
            'timeout' => 10, // seconds
        ],
        'openai' => [
            'adapter' => \App\Integrations\Ai\OpenAiAdapter::class,
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'base_url' => 'https://api.openai.com/v1',
            'timeout' => 10,
        ],
    ],
],
```

---

## 5. Market Data Integration (V1.5/V2)

### 5.1 Market Data Provider Interface

```php
interface MarketDataProviderInterface
{
    /**
     * Get current price for a ticker symbol.
     */
    public function getPrice(string $ticker, string $currency = 'IDR'): ?PriceResult;
    
    /**
     * Get prices for multiple tickers.
     */
    public function getPrices(array $tickers, string $currency = 'IDR'): array;
    
    /**
     * Get historical price for a date.
     */
    public function getHistoricalPrice(
        string $ticker, 
        string $date, 
        string $currency = 'IDR'
    ): ?PriceResult;
}
```

### 5.2 Price Sources

| Asset Type | Source | API | V1.5/V2 |
|-----------|--------|-----|---------|
| Stocks (IDX) | Yahoo Finance | `query1.finance.yahoo.com` (unofficial) | V1.5 |
| Stocks (IDX) | IDX API | `idx.co.id` (limited) | V2 alternative |
| Mutual Funds | Bareksa/Manual | No public API | Manual only |
| Crypto | CoinGecko | `api.coingecko.com` (free tier: 30 calls/min) | V1.5 |
| Crypto | Indodax | `indodax.com/api` (public, no key needed) | V1.5 |
| Gold | Manual | No reliable free Indonesian gold API | Manual |

### 5.3 Price Update Schedule

```php
// Laravel Scheduler (app/Console/Kernel.php)

// Crypto prices: every 15 minutes during trading hours
$schedule->job(new FetchCryptoPrices)->everyFifteenMinutes();

// Stock prices: daily at 16:30 WIB (after IDX close)
$schedule->job(new FetchStockPrices)->dailyAt('09:30'); // UTC = 16:30 WIB

// All prices: manual refresh available on-demand via UI button
```

### 5.4 Price Caching

- Cache fetched prices in `asset_accounts.current_price` and `asset_accounts.last_price_update`
- Show "last updated" timestamp in UI
- If price fetch fails, keep previous price and show "stale" indicator
- User can always manually override prices

---

## 6. Indodax Integration (V2)

### 6.1 API Analysis

Indodax provides a public REST API:
- **Base URL**: `https://indodax.com/api`
- **Authentication**: HMAC-SHA512 signature for private endpoints
- **Public endpoints** (no auth): ticker, trades, depth
- **Private endpoints** (auth required): balance, trade history, open orders

### 6.2 Useful Endpoints

| Endpoint | Method | Auth | Use Case |
|----------|--------|------|----------|
| `GET /api/ticker/{pair}` | GET | No | Get current price (e.g., `btcidr`) |
| `GET /api/summaries` | GET | No | Get all pair summaries |
| `POST /tapi` (getInfo) | POST | Yes | Get account balances |
| `POST /tapi` (tradeHistory) | POST | Yes | Get trade history |

### 6.3 Integration Scope (V2)

1. **Price fetching** (public, no auth): Get BTC/IDR, ETH/IDR prices
2. **Balance sync** (private, auth required): Read crypto balances
3. **Trade history import** (private): Import buy/sell history

### 6.4 Security for API Keys

- API key and secret stored encrypted in database (per-user)
- Encrypted with Laravel's `Crypt::encryptString()`
- Never logged or exposed in UI after initial entry
- User enters key once in Settings → Integrations

---

## 7. CSV Import/Export (V1.5 Export, V2 Import)

### 7.1 Export Format (V1.5)

#### Transaction Export
```csv
Date,Type,Amount,Currency,Category,Description,Account,Notes
2026-08-25,expense,15000,IDR,Food & Drink,Nasi Kuning,GoPay,Breakfast
2026-08-25,income,8000000,IDR,Salary,Monthly Salary,BCA,August salary
```

#### Portfolio Export
```csv
Name,Type,Platform,Ticker,Quantity,Avg Price,Current Price,Current Value,Unrealized P/L
BMRI @ Stockbit,stock,Stockbit,BMRI,500,5200,5500,2750000,150000
```

### 7.2 Import (V2)

Support importing from:
- App's own export format (re-import)
- Bibit transaction statement (CSV)
- Stockbit portfolio export (if available)
- Generic CSV with column mapping UI

Import process:
1. Upload CSV
2. Map columns to fields
3. Preview parsed transactions
4. User reviews and confirms
5. Bulk import with duplicate detection (by date + amount + description hash)

---

## 8. Exchange Rate Provider (V2)

### 8.1 Interface

```php
interface ExchangeRateProviderInterface
{
    public function getRate(string $from, string $to): ?float;
    public function getRates(string $base, array $targets): array;
}
```

### 8.2 Sources
- **ExchangeRate-API** (free tier: 1,500 requests/month)
- **Bank Indonesia API** (for official IDR rates)
- **Fallback**: Manual entry

Not needed for V1 (IDR only).

---

## 9. Integration Configuration Management

All integrations are configured through environment variables and the `config/finance.php` config file:

```php
// config/finance.php
return [
    'messaging' => [
        'default_provider' => env('MESSAGING_PROVIDER', 'fonnte'),
        'providers' => [
            'fonnte' => [
                'adapter' => \App\Integrations\WhatsApp\FonnteAdapter::class,
                'api_key' => env('FONNTE_API_KEY'),
                'webhook_token' => env('FONNTE_WEBHOOK_TOKEN'),
                'base_url' => 'https://api.fonnte.com',
            ],
            'meta' => [
                'adapter' => \App\Integrations\WhatsApp\MetaCloudApiAdapter::class,
                'access_token' => env('META_WHATSAPP_TOKEN'),
                'phone_number_id' => env('META_PHONE_NUMBER_ID'),
                'verify_token' => env('META_WEBHOOK_VERIFY_TOKEN'),
                'app_secret' => env('META_APP_SECRET'),
            ],
        ],
    ],
    
    'market_data' => [
        'crypto' => [
            'provider' => env('CRYPTO_PRICE_PROVIDER', 'coingecko'),
            'providers' => [
                'coingecko' => [
                    'adapter' => \App\Integrations\MarketData\CoinGeckoAdapter::class,
                    'base_url' => 'https://api.coingecko.com/api/v3',
                    'api_key' => env('COINGECKO_API_KEY'), // optional for free tier
                ],
                'indodax' => [
                    'adapter' => \App\Integrations\MarketData\IndodaxPriceAdapter::class,
                    'base_url' => 'https://indodax.com/api',
                ],
            ],
        ],
        'stocks' => [
            'provider' => env('STOCK_PRICE_PROVIDER', 'yahoo'),
        ],
    ],
    
    'ai' => [
        // See AI Specification
    ],
];
```

---

## 10. Integration Health Monitoring

### Health Check Endpoint (Internal)

```php
// GET /api/health/integrations (authenticated, admin only)
{
    "ai": {
        "provider": "gemini",
        "status": "ok",
        "last_successful_call": "2026-08-25T10:30:00Z",
        "error_rate_24h": 0.02
    },
    "whatsapp": {
        "provider": "fonnte",
        "status": "ok",
        "last_webhook_received": "2026-08-25T10:25:00Z"
    },
    "market_data": {
        "crypto": {
            "provider": "coingecko",
            "status": "ok",
            "last_price_update": "2026-08-25T10:15:00Z"
        },
        "stocks": {
            "provider": "yahoo",
            "status": "degraded",
            "last_error": "Rate limited"
        }
    }
}
```

### Failure Alerting
- If an integration fails > 5 times consecutively, log a warning
- UI shows integration status in Settings → Integrations
- Market prices show "stale" badge if last update > 24 hours
