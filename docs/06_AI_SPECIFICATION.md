# 06 — AI Specification

> **Document Version**: 1.0  
> **Last Updated**: 2026-08-25  
> **Status**: Approved for Implementation (V1.5)  
> **Cross-references**: [04_SYSTEM_ARCHITECTURE](./04_SYSTEM_ARCHITECTURE.md), [07_INTEGRATION_SPECIFICATION](./07_INTEGRATION_SPECIFICATION.md), [10_SECURITY_SPECIFICATION](./10_SECURITY_SPECIFICATION.md)

---

## 1. Overview

The AI subsystem provides natural language transaction parsing. Users can type (or send via WhatsApp) informal financial messages in Indonesian or English, and the system extracts structured transaction data.

**Critical Principle**: The AI is a parsing assistant, not an autonomous agent. It NEVER directly writes to the financial database. All AI output passes through validation and (when confidence is insufficient) human confirmation.

---

## 2. Architecture

```
┌─────────────────────────────────────────────────────┐
│                     INPUT LAYER                      │
│  ┌────────────┐  ┌────────────┐  ┌───────────────┐  │
│  │ Web Quick  │  │ WhatsApp   │  │ Future:       │  │
│  │ Input      │  │ Message    │  │ Telegram, etc │  │
│  └─────┬──────┘  └─────┬──────┘  └───────┬───────┘  │
│        └────────────────┼────────────────┘           │
│                         ▼                            │
│  ┌──────────────────────────────────────────────┐    │
│  │            AiParserService                    │    │
│  │  ┌────────────────────────────────────────┐   │    │
│  │  │  1. Normalize input                    │   │    │
│  │  │  2. Build prompt with context          │   │    │
│  │  │  3. Call AI provider                   │   │    │
│  │  │  4. Parse AI response                  │   │    │
│  │  │  5. Validate structured output         │   │    │
│  │  │  6. Score confidence                   │   │    │
│  │  │  7. Return ParsedTransaction           │   │    │
│  │  └────────────────────────────────────────┘   │    │
│  └──────────────────────┬───────────────────────┘    │
│                         ▼                            │
│  ┌──────────────────────────────────────────────┐    │
│  │         CONFIDENCE ROUTER                     │    │
│  │  HIGH (>0.85)  → Auto-create (confirmed)     │    │
│  │  MEDIUM (0.5-0.85) → Create as unconfirmed   │    │
│  │  LOW (<0.5)    → Reject, ask for clarification│    │
│  └──────────────────────┬───────────────────────┘    │
│                         ▼                            │
│  ┌──────────────────────────────────────────────┐    │
│  │      TransactionService.create()              │    │
│  │  (Standard transaction creation path)         │    │
│  └──────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────┘
```

---

## 3. AI Provider Interface

```php
interface AiProviderInterface
{
    /**
     * Parse a natural language financial message into structured data.
     *
     * @param string $message Raw user message
     * @param array $context User context (categories, accounts, recent transactions)
     * @return AiParseResult
     */
    public function parseTransaction(string $message, array $context): AiParseResult;
}
```

### AiParseResult Value Object

```php
class AiParseResult
{
    public function __construct(
        public readonly string $rawMessage,
        public readonly ?string $transactionType,    // 'income', 'expense', 'transfer'
        public readonly ?int $amount,                 // In smallest currency unit
        public readonly ?string $currency,            // 'IDR'
        public readonly ?string $date,                // 'YYYY-MM-DD'
        public readonly ?string $categorySlug,        // Matched category
        public readonly ?string $description,         // Extracted description
        public readonly ?string $notes,               // Additional notes
        public readonly ?string $accountHint,         // e.g., 'gopay', 'cash', 'bca'
        public readonly float $confidence,            // 0.0 to 1.0
        public readonly array $missingFields,         // Fields AI couldn't determine
        public readonly string $provider,             // 'openai', 'gemini'
        public readonly string $model,                // 'gpt-4o-mini'
        public readonly string $promptVersion,        // 'v1.0'
        public readonly int $responseTimeMs,          // Response time
    ) {}
}
```

---

## 4. Prompt Design

### 4.1 System Prompt (v1.0)

```
You are a financial transaction parser for an Indonesian personal finance application.

Your job is to extract structured transaction data from informal Indonesian or English messages.

RULES:
1. Extract: transaction type, amount, description, category, date, account hint
2. Amount MUST be in Indonesian Rupiah (IDR) as an integer (no decimals)
3. If the user writes "15rb" or "15ribu" or "15k", that means 15,000
4. If the user writes "1.5jt" or "1,5juta", that means 1,500,000
5. If no date is mentioned, use today's date
6. If the category is unclear, set category to null and explain in missing_fields
7. If the amount is unclear, set amount to null
8. Common Indonesian financial terms:
   - gajian = salary (income)
   - lembur = overtime (income)
   - THR = holiday bonus (income)
   - beli/beli-beli = purchase (expense)
   - makan/sarapan/makan siang/makan malam = food (expense)
   - bensin/isi bensin = fuel/transportation (expense)
   - top up/topup = e-wallet top-up (transfer)
   - transfer/tf = transfer
   - bayar/bayarin = pay (could be expense or liability payment)
   - cicilan = installment (liability payment)
   - tagihan = bill (expense)
   - belanja = shopping (expense)
   - jajan = snack/treat (expense)
   - parkir = parking (expense)
   - ojol = online ride-hailing (expense)
   - grab/gojek = ride-hailing (expense)
   
8. Time expressions:
   - tadi = earlier today
   - barusan = just now
   - kemarin = yesterday
   - tadi pagi = this morning
   - tadi siang = this afternoon
   - tadi malam = last night/earlier tonight
   - minggu lalu = last week

AVAILABLE CATEGORIES:
{categories_json}

AVAILABLE ACCOUNTS:
{accounts_json}

Respond ONLY with valid JSON matching this schema:
{
  "transaction_type": "income" | "expense" | "transfer" | null,
  "amount": integer | null,
  "currency": "IDR",
  "date": "YYYY-MM-DD" | null,
  "category_slug": string | null,
  "description": string | null,
  "notes": string | null,
  "account_hint": string | null,
  "confidence": float (0.0 to 1.0),
  "missing_fields": string[],
  "reasoning": string
}
```

### 4.2 Prompt Versioning

| Version | Changes | Date |
|---------|---------|------|
| v1.0 | Initial prompt | Launch |
| v1.1 | Add new categories, fix edge cases | After 2 weeks |
| v1.2 | Improve amount parsing accuracy | After 1 month |

Prompt versions are stored in config and logged with every AI parsing event. This enables:
- A/B testing prompts
- Rollback to previous versions
- Accuracy tracking per version

### 4.3 Context Injection

The prompt includes user-specific context:
- User's category list (names + slugs)
- User's account list (names)
- Optionally: last 5 transactions (for pattern matching, e.g., "same as yesterday")

Context is kept minimal to reduce token usage and cost.

---

## 5. Amount Normalization

Before sending to AI, the service pre-processes the message to normalize obvious amounts. This reduces AI errors and provides a fallback if AI fails.

### 5.1 Indonesian Amount Patterns

| Pattern | Value | Regex |
|---------|-------|-------|
| `15rb` / `15ribu` | 15,000 | `(\d+)\s*(rb\|ribu)` |
| `15k` | 15,000 | `(\d+)\s*k\b` |
| `1.5jt` / `1,5juta` / `1.5juta` | 1,500,000 | `(\d+[.,]\d+)\s*(jt\|juta)` |
| `2jt` / `2juta` | 2,000,000 | `(\d+)\s*(jt\|juta)` |
| `500rb` | 500,000 | `(\d+)\s*(rb\|ribu)` |
| `2.000.000` | 2,000,000 | `(\d{1,3}(?:\.\d{3})+)` (Indonesian thousand sep) |
| `2,000,000` | 2,000,000 | `(\d{1,3}(?:,\d{3})+)` |
| `dua ratus ribu` | 200,000 | Word-to-number converter |
| `se-juta` / `sejuta` | 1,000,000 | Special case |
| `setengah juta` | 500,000 | Special case |

### 5.2 Pre-Processing Pipeline

```
Raw message
  ├─ 1. Trim whitespace, normalize unicode
  ├─ 2. Detect and extract amount (regex patterns)
  ├─ 3. Detect date expressions (kemarin, tadi, etc.)
  ├─ 4. Pass normalized message + extracted hints to AI
  └─ 5. AI fills in remaining fields (type, category, description)
```

This hybrid approach (regex + AI) is more reliable than pure AI parsing for amounts.

---

## 6. Confidence Scoring

### 6.1 Confidence Levels

| Level | Range | Action |
|-------|-------|--------|
| **High** | 0.85 - 1.00 | Auto-create confirmed transaction |
| **Medium** | 0.50 - 0.84 | Create as unconfirmed; ask user to review |
| **Low** | 0.00 - 0.49 | Do NOT create; ask user to clarify |

### 6.2 Confidence Factors

The AI model provides its own confidence, but the service adjusts it based on:

| Factor | Adjustment |
|--------|------------|
| Amount successfully extracted by regex | +0.10 |
| Amount only from AI (no regex match) | -0.05 |
| Category matched to existing category | +0.05 |
| Category unclear | -0.15 |
| Transaction type unclear | -0.20 |
| Message is very short (< 3 words) | -0.10 |
| Message contains clear financial verb (beli, bayar, gajian) | +0.05 |

### 6.3 Confirmation Flow

#### Web Quick Input
- HIGH: Show green checkmark preview → auto-save after 3 seconds (user can cancel)
- MEDIUM: Show yellow preview with editable fields → user must click "Confirm"
- LOW: Show error message → "Could not parse. Please enter manually."

#### WhatsApp (V1.5)
- HIGH: Save and send confirmation: "✅ Recorded: Expense Rp15.000 - Nasi Kuning (Food)"
- MEDIUM: Send preview: "📝 Rp15.000 for Nasi Kuning? Category: Food? Reply 'ya' to confirm or correct."
- LOW: Send question: "❓ I don't understand. How much did you spend and for what?"

---

## 7. Error Handling

| Error | Handling |
|-------|----------|
| AI API timeout | Retry once after 2 seconds; if fail, prompt manual entry |
| AI API rate limit | Queue with backoff; inform user of delay |
| AI returns invalid JSON | Log error, prompt manual entry |
| AI returns absurd amount (> Rp 1 billion for daily expense) | Flag as suspicious, lower confidence to MEDIUM |
| AI returns negative amount | Reject, prompt manual entry |
| AI provider outage | Switch to fallback provider (if configured); otherwise prompt manual entry |

---

## 8. AI Provider Comparison

### V1.5 Recommendation: Start with Google Gemini Flash

| Criteria | OpenAI (GPT-4o-mini) | Google Gemini 2.0 Flash |
|----------|----------------------|------------------------|
| Cost | ~$0.15/1M input tokens | ~$0.075/1M input tokens |
| Speed | ~500ms | ~300ms |
| Indonesian language | Good | Good |
| JSON mode | Native (response_format) | Native (response_mime_type) |
| Free tier | Limited | 15 RPM free |
| Recommendation | Fallback | **Primary** |

### Provider Switching
The `AiProviderInterface` allows switching providers by changing a config value:

```php
// config/finance.php
'ai' => [
    'default_provider' => env('AI_PROVIDER', 'gemini'),
    'providers' => [
        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'model' => 'gemini-2.0-flash',
        ],
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => 'gpt-4o-mini',
        ],
    ],
    'confidence' => [
        'high_threshold' => 0.85,
        'medium_threshold' => 0.50,
    ],
    'prompt_version' => 'v1.0',
],
```

---

## 9. Logging & Monitoring

Every AI parsing attempt is logged in `ai_parsing_logs`:
- Raw input message
- Parsed output (JSONB)
- Confidence score
- Provider and model used
- Prompt version
- Response time
- Final status (confirmed, rejected, error)
- Linked transaction ID (if created)

### Metrics to Track
- **Accuracy rate**: % of confirmed parses / total parses
- **Auto-confirm rate**: % of HIGH confidence / total parses
- **Average confidence**: Mean confidence across all parses
- **Error rate**: % of errors / total attempts
- **Average response time**: Mean AI response time

These metrics inform prompt improvement and provider selection decisions.

---

## 10. Security Considerations

1. **Never send sensitive data to AI**: No account balances, no full names, no account numbers
2. **Sanitize input**: Strip potential injection content before sending to AI
3. **Rate limit AI calls**: Max 30 parses per user per hour
4. **Log all AI interactions**: For audit and debugging
5. **AI output is untrusted**: Always validate against schema before using
6. **Prompt injection defense**: System prompt instructs AI to ignore user attempts to change behavior
7. **Cost monitoring**: Set monthly AI API cost alerts

---

## 11. Testing Strategy

### Unit Tests
- Amount normalization (regex patterns): Test all Indonesian amount formats
- Confidence calculation: Test adjustment factors
- Parse result validation: Test schema validation

### Integration Tests
- End-to-end: message → AI API → parsed result → transaction creation
- Use recorded AI responses (VCR pattern) for deterministic tests
- Test with real AI API in staging (not in CI)

### Test Cases

| Input | Expected Type | Expected Amount | Expected Category | Notes |
|-------|--------------|----------------|-------------------|-------|
| "beli nasi goreng 15rb" | expense | 15000 | food | Standard case |
| "gajian 8jt" | income | 8000000 | salary | Income detection |
| "transfer bca ke mandiri 1jt" | transfer | 1000000 | — | Transfer detection |
| "tadi beli sesuatu 50rb" | expense | 50000 | null (ask) | Ambiguous category |
| "kemarin bayar listrik 350ribu" | expense | 350000 | bills | Date = yesterday |
| "overtime 500k" | income | 500000 | overtime | English + Indonesian |
| "isi gopay 200rb" | transfer | 200000 | — | E-wallet top-up |
| "cicilan motor 1.5jt" | liability_payment | 1500000 | — | Liability payment |
| "" | error | null | null | Empty message |
| "halo" | error | null | null | No financial content |
| "beli btc 500rb" | asset_purchase | 500000 | — | Asset purchase (V2) |
