# 03 — Feature Specification

> **Document Version**: 1.0  
> **Last Updated**: 2026-08-25  
> **Status**: Approved for Implementation  
> **Cross-references**: [01_PRD](./01_PRD.md), [02_PRODUCT_SCOPE](./02_PRODUCT_SCOPE.md), [05_DATABASE_SCHEMA](./05_DATABASE_SCHEMA.md)

---

## 1. Authentication (V1)

### 1.1 Registration
- **Fields**: name, email, password, password confirmation
- **Validation**: email uniqueness, password min 8 chars, password confirmation match
- **Behavior**: Create user → seed default categories → redirect to dashboard
- **Default categories**: See Section 7

### 1.2 Login
- **Fields**: email, password, remember me (checkbox)
- **Behavior**: Laravel session-based auth; "remember me" extends session to 30 days
- **Rate limiting**: Max 5 failed attempts per minute per IP; lockout 60 seconds

### 1.3 Logout
- Destroy session, redirect to login

### 1.4 Profile Management
- Update name, email
- Change password (requires current password)
- Theme preference (dark/light/system)
- Currency display preference (future use)
- Timezone (default: Asia/Jakarta)

---

## 2. Account Management (V1)

### 2.1 Account Types

| Type | Slug | Description | Default Balance |
|------|------|-------------|----------------|
| Cash | `cash` | Physical cash | 0 |
| Bank Account | `bank` | Bank savings/checking | 0 |
| E-Wallet | `ewallet` | Digital wallet (GoPay, OVO, etc.) | 0 |

### 2.2 Account Fields

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| name | string(100) | Yes | e.g., "BCA", "Cash Wallet", "GoPay" |
| type | enum | Yes | cash, bank, ewallet |
| currency | string(3) | Yes | Default: "IDR" |
| initial_balance | integer | Yes | Opening balance in smallest unit |
| current_balance | integer | Computed | Calculated from entries; cached |
| icon | string(50) | No | Icon identifier |
| color | string(7) | No | Hex color code |
| description | string(255) | No | Optional notes |
| is_active | boolean | Yes | Default: true |
| sort_order | integer | Yes | Display ordering |

### 2.3 Account Operations
- **Create**: Set initial balance via adjustment transaction
- **Edit**: Name, icon, color, description, sort order, active status
- **Deactivate**: Soft-deactivate (hide from dropdowns, keep in history)
- **Delete**: Only if no transactions reference it; otherwise deactivate
- **View Balance**: Show current balance, recent transactions
- **Balance Reconciliation**: "Adjust balance" creates an adjustment transaction to match actual balance

### 2.4 Business Rules
- Account names must be unique per user
- Deleting an account with transactions is blocked (must deactivate)
- Initial balance is recorded as an adjustment transaction on creation
- Balance is denormalized (cached on account row) AND verifiable via SUM of entries

---

## 3. Transaction System (V1)

### 3.1 Transaction Types

| Type | Code | Source Entry | Destination Entry | Cash Flow Impact |
|------|------|-------------|-------------------|-----------------|
| Income | `income` | Income source (virtual) | Account (credit) | +Income |
| Expense | `expense` | Account (debit) | Expense category (virtual) | +Expense |
| Transfer | `transfer` | Account A (debit) | Account B (credit) | None |
| Asset Purchase | `asset_purchase` | Account (debit) | Asset account (credit) | None |
| Asset Sale | `asset_sale` | Asset account (debit) | Account (credit) | None |
| Liability Payment | `liability_payment` | Account (debit) | Liability (credit) | None |
| Liability Increase | `liability_increase` | Liability (debit) | Account (credit) | None |
| Adjustment | `adjustment` | System (virtual) | Account/Asset/Liability | None |

### 3.2 Transaction Header Fields

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| type | enum | Yes | See transaction types above |
| date | date | Yes | Transaction date (not timestamp) |
| amount | bigint | Yes | Gross amount in smallest currency unit |
| currency | string(3) | Yes | Default: "IDR" |
| description | string(255) | Yes | Human-readable description |
| notes | text | No | Additional notes |
| category_id | FK | Conditional | Required for income/expense; null for transfers |
| reference_number | string(100) | No | Receipt/reference number |
| is_confirmed | boolean | Yes | Default: true; false for AI-pending transactions |
| source | enum | Yes | 'manual', 'ai_web', 'whatsapp', 'recurring', 'import' |

### 3.3 Transaction Entries (Double-Entry Lines)

Each transaction has exactly 2 entries (for V1; multi-entry splits can be added later).

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| transaction_id | FK | Yes | Parent transaction |
| account_id | FK | Conditional | FK to accounts, asset_accounts, or liabilities |
| account_type | string | Yes | 'account', 'asset_account', 'liability', 'virtual' |
| entry_type | enum | Yes | 'debit' or 'credit' |
| amount | bigint | Yes | Always positive |

**Virtual accounts**: For income sources and expense categories, the entry references a category rather than a real account. This keeps the double-entry model consistent without requiring the user to manage "income accounts" and "expense accounts."

### 3.4 Transaction Creation Flows

#### Income
```
User provides: amount, category, account (destination), description, date
System creates:
  Transaction { type: income, amount: 50000, ... }
  Entry { account_type: virtual, entry_type: debit, amount: 50000 }    // Income source
  Entry { account_id: bank_bca, entry_type: credit, amount: 50000 }    // Cash increases
```

#### Expense
```
User provides: amount, category, account (source), description, date
System creates:
  Transaction { type: expense, amount: 25000, ... }
  Entry { account_id: ewallet_gopay, entry_type: debit, amount: 25000 } // Cash decreases
  Entry { account_type: virtual, entry_type: credit, amount: 25000 }    // Expense category
```

#### Transfer
```
User provides: amount, source account, destination account, description, date
System creates:
  Transaction { type: transfer, amount: 1000000, ... }
  Entry { account_id: bank_bca, entry_type: debit, amount: 1000000 }    // Source decreases
  Entry { account_id: bank_mandiri, entry_type: credit, amount: 1000000 } // Dest increases
```

#### Asset Purchase
```
User provides: amount, source account, asset account, quantity, price_per_unit, date
System creates:
  Transaction { type: asset_purchase, amount: 500000, ... }
  Entry { account_id: bank_bca, entry_type: debit, amount: 500000 }           // Cash decreases
  Entry { account_id: asset_btc_indodax, entry_type: credit, amount: 500000 } // Asset increases
  AssetTransaction { asset_account_id, quantity: 0.005, price_per_unit: 100000000, ... }
```

### 3.5 Transaction Edit/Delete Rules
- Editing a transaction: Reverse old entries, create new entries (within DB transaction)
- Deleting a transaction: Reverse entries, recalculate affected balances
- Confirmed transactions can be edited/deleted
- AI-pending (unconfirmed) transactions can be confirmed, edited, or rejected

### 3.6 Transaction Search & Filter

| Filter | Type | Notes |
|--------|------|-------|
| search | text | Full-text on description and notes |
| date_from | date | Start date (inclusive) |
| date_to | date | End date (inclusive) |
| type | enum[] | Filter by transaction type(s) |
| category_id | FK[] | Filter by category(ies) |
| account_id | FK[] | Filter by account(s) |
| amount_min | integer | Minimum amount |
| amount_max | integer | Maximum amount |
| source | enum[] | Filter by source (manual, whatsapp, etc.) |

**Sorting**: date (default desc), amount, description  
**Pagination**: 25 items per page (mobile), 50 (desktop)

---

## 4. Asset & Investment Management (V1)

### 4.1 Asset Types

| Type | Slug | Quantity Precision | Price Precision |
|------|------|-------------------|----------------|
| Stock | `stock` | Integer (lots × 100 shares) | DECIMAL(20,2) |
| Mutual Fund | `mutual_fund` | DECIMAL(20,4) | DECIMAL(20,4) |
| Cryptocurrency | `crypto` | DECIMAL(20,8) | DECIMAL(20,2) |
| Other Investment | `other_investment` | DECIMAL(20,4) | DECIMAL(20,2) |
| Gold | `gold` | DECIMAL(20,4) grams | DECIMAL(20,2) |
| Property | `property` | N/A (value-only) | N/A |
| Vehicle | `vehicle` | N/A (value-only) | N/A |
| Other Asset | `other_asset` | N/A (value-only) | N/A |

### 4.2 Asset Account Fields

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| name | string(100) | Yes | e.g., "BMRI @ Stockbit" |
| type | enum | Yes | See asset types above |
| platform | string(100) | No | e.g., "Stockbit", "Bibit", "Indodax" |
| ticker | string(20) | Conditional | Required for stock/crypto; e.g., "BMRI", "BTC" |
| currency | string(3) | Yes | Default: "IDR" |
| quantity | decimal | Conditional | Current holding quantity |
| avg_purchase_price | decimal | Conditional | Weighted average cost |
| current_price | decimal | No | Last known market price |
| current_value | bigint | Computed | quantity × current_price (or manual value for property/vehicle) |
| manual_value | bigint | Conditional | For property/vehicle/other (no quantity tracking) |
| unrealized_pnl | bigint | Computed | current_value - (quantity × avg_purchase_price) |
| last_price_update | timestamp | No | When current_price was last updated |
| is_active | boolean | Yes | Default: true |
| notes | text | No | Additional information |

### 4.3 Asset Transactions

When a user buys or sells an asset, an `AssetTransaction` is created alongside the main `Transaction`.

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| asset_account_id | FK | Yes | Which asset |
| transaction_id | FK | Yes | Parent transaction |
| type | enum | Yes | 'buy' or 'sell' |
| quantity | decimal | Yes | Units bought/sold |
| price_per_unit | decimal | Yes | Price per unit at transaction time |
| total_amount | bigint | Yes | Total cost/proceeds |
| fees | bigint | No | Transaction fees |

### 4.4 Average Price Calculation

On every buy/sell, recalculate the weighted average purchase price:

**Buy**: `new_avg = (old_quantity × old_avg + new_quantity × new_price) / (old_quantity + new_quantity)`

**Sell**: Average price doesn't change on sell (FIFO or average cost basis — V1 uses average cost for simplicity).

### 4.5 Portfolio View

Display:
- List of all asset accounts grouped by type
- Per asset: name, ticker, quantity, avg price, current price, current value, unrealized P/L, P/L %
- Totals by type (stocks total, crypto total, etc.)
- Overall portfolio total
- Allocation pie chart

---

## 5. Liability Management (V1)

### 5.1 Liability Types

| Type | Slug | Description |
|------|------|-------------|
| Credit Card | `credit_card` | Revolving credit |
| Personal Loan | `personal_loan` | Fixed-term loan (KTA, etc.) |
| Installment | `installment` | Fixed installment payment |
| Other Debt | `other_debt` | Miscellaneous debt |

### 5.2 Liability Fields

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| name | string(100) | Yes | e.g., "BCA Credit Card" |
| type | enum | Yes | See liability types above |
| currency | string(3) | Yes | Default: "IDR" |
| initial_balance | bigint | Yes | Original amount owed |
| current_balance | bigint | Computed | Remaining balance |
| credit_limit | bigint | Conditional | For credit cards |
| interest_rate | decimal(5,2) | No | Annual interest rate % |
| minimum_payment | bigint | No | Minimum monthly payment |
| due_date_day | integer(1-31) | No | Day of month payment is due |
| start_date | date | No | When the liability started |
| end_date | date | No | Expected payoff date |
| remaining_terms | integer | Conditional | For installments |
| is_active | boolean | Yes | Default: true |
| notes | text | No | Additional information |

### 5.3 Liability Operations
- **Create**: Set initial balance
- **Payment**: Creates a `liability_payment` transaction; reduces liability balance, reduces account balance
- **Additional charge**: Creates a `liability_increase` transaction (e.g., new credit card charge)
- **View**: Balance, payment history, remaining terms, next due date
- **Close**: Mark as paid off (is_active = false)

---

## 6. Dashboard (V1)

### 6.1 Layout Structure

```
┌──────────────────────────────────────┐
│           NET WORTH CARD             │
│     Rp 125,450,000 ▲ +2.3%          │
│   (vs last month)                    │
├──────────────────┬───────────────────┤
│  TOTAL ASSETS    │ TOTAL LIABILITIES │
│  Rp 150,000,000  │ Rp 24,550,000    │
├──────────────────┴───────────────────┤
│       MONTHLY CASH FLOW             │
│  Income: Rp 8,000,000               │
│  Expense: Rp 3,200,000              │
│  Net: Rp 4,800,000                  │
│  [=========>........] 60% saved      │
├──────────────────────────────────────┤
│       ASSET ALLOCATION              │
│  [Pie Chart: Cash 40%, Stocks 30%,  │
│   Mutual Funds 15%, Crypto 10%,     │
│   Other 5%]                         │
├──────────────────────────────────────┤
│       RECENT TRANSACTIONS            │
│  • Breakfast - Nasi Kuning  -15,000 │
│  • Grab Ride              -25,000   │
│  • Salary               +8,000,000  │
│  • Internet Bill          -350,000  │
│  [View All →]                       │
└──────────────────────────────────────┘
```

### 6.2 Dashboard Data Points

| Section | Data | Source |
|---------|------|--------|
| Net Worth | Total assets - total liabilities | AccountService + AssetService + LiabilityService |
| Net Worth Change | Compare current vs 1 month ago | Net worth snapshots table |
| Total Assets | Sum of all account balances + all asset values | AccountService + AssetService |
| Total Liabilities | Sum of all liability balances | LiabilityService |
| Monthly Cash Flow | Sum of income, sum of expenses, net | TransactionService (current month) |
| Savings Rate | (Income - Expense) / Income × 100 | Calculated |
| Asset Allocation | Group asset values by type | AssetService |
| Recent Transactions | Last 10 transactions | TransactionService |

### 6.3 Net Worth Snapshots
The system takes a daily snapshot of net worth for historical tracking. A scheduled job (`SnapshotNetWorth`) runs daily at midnight to record:
- Date
- Total cash (sum of all accounts)
- Total investments (sum of all asset values)
- Total liabilities (sum of all liability balances)
- Net worth

This enables the net worth trend chart without expensive historical recalculations.

---

## 7. Category System (V1)

### 7.1 Default Categories

#### Income Categories
| Name | Icon | Color |
|------|------|-------|
| Salary | `briefcase` | `#10B981` |
| Overtime | `clock` | `#059669` |
| Freelance | `laptop` | `#34D399` |
| Bonus | `gift` | `#6EE7B7` |
| Investment Income | `trending-up` | `#047857` |
| Other Income | `plus-circle` | `#A7F3D0` |

#### Expense Categories
| Name | Icon | Color |
|------|------|-------|
| Food & Drink | `utensils` | `#EF4444` |
| Transportation | `car` | `#F97316` |
| Housing | `home` | `#8B5CF6` |
| Bills & Utilities | `zap` | `#EC4899` |
| Shopping | `shopping-bag` | `#F59E0B` |
| Entertainment | `film` | `#6366F1` |
| Health | `heart` | `#14B8A6` |
| Education | `book-open` | `#3B82F6` |
| Subscriptions | `repeat` | `#A855F7` |
| Family | `users` | `#F472B6` |
| Personal Care | `scissors` | `#FB923C` |
| Other Expense | `more-horizontal` | `#9CA3AF` |

### 7.2 Category Fields

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| name | string(100) | Yes | Category name |
| type | enum | Yes | 'income' or 'expense' |
| parent_id | FK (self) | No | For subcategories |
| icon | string(50) | Yes | Icon identifier |
| color | string(7) | Yes | Hex color |
| is_system | boolean | Yes | Default: false; system categories can't be deleted |
| is_active | boolean | Yes | Default: true |
| sort_order | integer | Yes | Display ordering |

### 7.3 Subcategory Support
Categories support one level of nesting. Example:
- Food & Drink
  - Breakfast
  - Lunch
  - Dinner
  - Snacks
  - Coffee
  - Groceries

Subcategories are optional. Transactions can be assigned to either a parent category or a subcategory.

---

## 8. Reports (V1)

### 8.1 Available Reports

#### Monthly Income/Expense Report
- Bar chart: income vs expense by month (last 6-12 months)
- Table: category breakdown for selected month
- Comparison with previous month

#### Cash Flow Report
- Line chart: net cash flow over time
- Income trend line, expense trend line
- Monthly/weekly/daily granularity

#### Expense by Category
- Pie chart: expense distribution by category
- List: category amounts sorted by highest
- Period selector (this month, last month, custom range)

#### Net Worth Trend
- Line chart: net worth over time (from daily snapshots)
- Breakdown: cash vs investments vs liabilities over time
- Period: last 3 months, 6 months, 1 year, all time

#### Asset Allocation
- Pie/donut chart: current asset allocation
- Table: asset type, value, percentage

### 8.2 Report Parameters
All reports support:
- Date range (predefined: this month, last month, last 3 months, last 6 months, YTD, last year, all time, custom)
- Account filter (optional)

---

## 9. Settings (V1)

### 9.1 Settings Structure

| Setting | Type | Default | Notes |
|---------|------|---------|-------|
| Theme | enum | system | dark, light, system |
| Default account | FK | First account | For quick transaction entry |
| Default currency | string | IDR | Display currency |
| Timezone | string | Asia/Jakarta | For date display |
| Date format | enum | DD/MM/YYYY | DD/MM/YYYY, MM/DD/YYYY, YYYY-MM-DD |
| Start of month | integer | 1 | 1-28; for monthly calculations (some users get paid on 25th) |

### 9.2 Account Management (Settings sub-page)
- Manage accounts (CRUD)
- Reorder accounts
- Deactivate/reactivate accounts

### 9.3 Category Management (Settings sub-page)
- Manage categories (CRUD)
- Add subcategories
- Reorder categories
- Deactivate/reactivate categories

---

## 10. Quick Transaction Entry (V1)

### 10.1 Mobile Quick Add
Accessible via the **"+"** button (FAB on mobile, button on desktop).

**Modal/Sheet Flow**:
1. Select type: Income / Expense / Transfer / More...
2. For Income/Expense:
   - Amount input (large numeric keypad on mobile)
   - Category selector (grid of icons)
   - Account selector (defaults to user's default account)
   - Description (text input)
   - Date (defaults to today)
   - Notes (optional, collapsible)
3. Save → close modal → show success toast

**Design priority**: The most common action (recording an expense) should take ≤ 3 taps + typing the amount and a short description.

### 10.2 AI Quick Input (V1.5)
A text input field where the user types natural language:
- "beli makan 25rb"
- "gajian 8juta"
- "transfer bca ke mandiri 1juta"

The AI parses the input, shows a preview card with extracted data, and the user confirms or edits before saving.

---

## 11. Recurring Transactions (V1.5)

### 11.1 Template Fields

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| name | string(100) | Yes | Template name |
| type | enum | Yes | income, expense |
| amount | bigint | Yes | Transaction amount |
| category_id | FK | Yes | Category |
| account_id | FK | Yes | Account |
| description | string(255) | Yes | Transaction description |
| frequency | enum | Yes | daily, weekly, biweekly, monthly, yearly |
| day_of_month | integer | Conditional | For monthly (1-28) |
| day_of_week | integer | Conditional | For weekly (0-6) |
| start_date | date | Yes | When to start generating |
| end_date | date | No | When to stop (null = indefinite) |
| is_active | boolean | Yes | Default: true |
| auto_confirm | boolean | Yes | Default: false; if true, auto-confirm generated transactions |
| last_generated_date | date | No | Track last generation |

### 11.2 Generation Logic
- Laravel Scheduler runs daily
- Check all active recurring templates
- If next occurrence date ≤ today AND last_generated_date < next occurrence date:
  - Generate transaction (confirmed or unconfirmed based on auto_confirm)
  - Update last_generated_date
- User can review and confirm/edit/delete generated transactions

---

## 12. Budgeting (V1.5)

### 12.1 Budget Fields

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| name | string(100) | Yes | Budget name (e.g., "August 2026") |
| period_start | date | Yes | Budget period start |
| period_end | date | Yes | Budget period end |
| total_budget | bigint | No | Overall spending limit |

### 12.2 Budget Item Fields

| Field | Type | Required | Notes |
|-------|------|----------|-------|
| budget_id | FK | Yes | Parent budget |
| category_id | FK | Yes | Category this limit applies to |
| amount | bigint | Yes | Budget limit for this category |
| spent | bigint | Computed | Sum of expenses in this category during period |
| remaining | bigint | Computed | amount - spent |

### 12.3 Budget Display
- Progress bar per category (spent / budget)
- Color coding: green (< 75%), yellow (75-90%), red (> 90%)
- Overall budget progress
- "Over budget" alerts
