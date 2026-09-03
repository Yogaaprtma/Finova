# 02 — Product Scope

> **Document Version**: 1.0  
> **Last Updated**: 2026-08-25  
> **Status**: Approved for Implementation  
> **Cross-references**: [01_PRD](./01_PRD.md), [03_FEATURE_SPECIFICATION](./03_FEATURE_SPECIFICATION.md), [12_IMPLEMENTATION_ROADMAP](./12_IMPLEMENTATION_ROADMAP.md)

---

## 1. Scope Philosophy

This application is a **personal finance management tool**, not:
- An enterprise accounting system
- A banking application
- A trading platform
- A social finance app

Every feature must justify its inclusion by directly serving one of these goals:
1. Accurately recording financial transactions
2. Providing clear financial visibility
3. Making daily financial tracking effortless
4. Maintaining data integrity

Features that don't serve these goals are deferred or rejected.

---

## 2. Version Scope Matrix

### V1 — Core Financial Tracking (MVP)

**Timeline Target**: 6-8 weeks  
**Goal**: A fully functional personal finance tracker with manual entry, accurate balance tracking, and a modern dashboard.

| Module | Features |
|--------|----------|
| **Authentication** | Registration, login, logout, session management, CSRF |
| **Accounts** | CRUD for cash, bank, e-wallet accounts; balance tracking |
| **Transactions** | Income, expense, transfer, asset purchase/sale, liability payment; all with simplified double-entry |
| **Categories** | Default category set; user can create/edit/deactivate categories and subcategories |
| **Assets & Investments** | Manual tracking: stocks, mutual funds, crypto, other; quantity, avg price, current value, unrealized P/L |
| **Liabilities** | Manual tracking: credit cards, loans, installments; balance, payments |
| **Dashboard** | Net worth, total assets breakdown, total liabilities breakdown, monthly cash flow, recent transactions |
| **Reports** | Monthly income/expense report, cash flow chart, net worth trend, expense by category |
| **Search & Filter** | Full-text search on transaction descriptions; filter by date, category, account, amount range, type |
| **PWA** | Web app manifest, service worker (cache shell), installable on mobile, standalone display mode |
| **UI/UX** | Mobile-first responsive design, bottom navigation (mobile), sidebar (desktop), dark/light mode |
| **Data Integrity** | Integer arithmetic for IDR, simplified double-entry model, balance consistency checks |

### V1.5 — Intelligence & Automation

**Timeline Target**: 4-6 weeks after V1  
**Goal**: Add AI-powered input, WhatsApp channel, budgeting, and automation features.

| Module | Features |
|--------|----------|
| **AI Transaction Parser** | Natural language input box (web); Indonesian language support; amount/category/description extraction; confidence scoring; confirmation flow |
| **WhatsApp Integration** | Receive messages via webhook; parse with AI; confirm/reject flow; response messages |
| **Budgeting** | Monthly budgets; per-category budget limits; spending progress; remaining budget display |
| **Recurring Transactions** | Define templates (salary, rent, subscriptions); auto-generate on schedule; review before posting |
| **CSV Export** | Export transactions, accounts, portfolio data to CSV |
| **Market Prices** | Fetch stock/mutual fund prices from public sources; fetch crypto prices from CoinGecko/Indodax; manual override |
| **Quick Actions** | Shortcut buttons on dashboard for common transactions |

### V2 — Expansion & Integration

**Timeline Target**: 8-12 weeks after V1.5  
**Goal**: External integrations, advanced features, multi-currency.

| Module | Features |
|--------|----------|
| **Financial Goals** | Goal creation, target amount/date, progress tracking, linked accounts |
| **AI Insights** | Spending pattern analysis, anomaly detection, trend alerts |
| **Indodax Integration** | API-based portfolio sync, transaction history import |
| **CSV Import** | Import Bibit/Stockbit statement CSVs; map columns to fields |
| **Multi-Currency** | Currency field on all amounts; exchange rate management; converted display |
| **Advanced Reports** | Investment performance (TWR/MWR), asset allocation history, liability amortization |
| **Attachments** | Receipt photos, document uploads per transaction |
| **Offline Support** | Service worker caching; IndexedDB for offline entries; sync queue; conflict resolution |
| **Multi-User** | User roles, shared accounts (optional), invitation system |

---

## 3. Feature Exclusions (Not Planned)

| Feature | Reason |
|---------|--------|
| Real-time stock trading | This is a tracker, not a brokerage |
| Bill pay / payment processing | Security and regulatory complexity |
| Tax filing / tax calculation | Regulatory complexity; defer to dedicated tools |
| Social features (sharing expenses, split bills) | Out of scope for personal finance |
| Gamification (badges, streaks) | Doesn't serve core financial tracking goals |
| Email transaction parsing | Low priority; WhatsApp covers the use case |
| Bank account sync (screen scraping) | Legal/ToS risks; unreliable |
| Full double-entry accounting | Over-engineered for personal use; simplified model is sufficient |

---

## 4. Boundary Definitions

### What is an "Account"?
A container for money that the user controls. Each account has a single currency and a balance.

| Account Type | Examples | Balance Type |
|-------------|----------|-------------|
| Cash | Physical wallet | Positive |
| Bank | BCA, Mandiri, BNI | Positive |
| E-Wallet | GoPay, OVO, Dana, ShopeePay | Positive |

### What is an "Asset Account"?
A tracked investment position within a specific platform.

| Asset Type | Examples | Tracking Fields |
|-----------|----------|----------------|
| Stock | BMRI @ Stockbit | Quantity (lots), avg price, current price |
| Mutual Fund | RDPU @ Bibit | Units (decimal), avg NAV, current NAV |
| Crypto | BTC @ Indodax | Quantity (8 decimals), avg price, current price |
| Other Investment | Gold, P2P lending | Value (manual) |
| Property | House, land | Estimated value (manual) |
| Vehicle | Car, motorcycle | Estimated value (manual) |

### What is a "Liability"?
A financial obligation the user owes.

| Liability Type | Examples | Tracking Fields |
|---------------|----------|----------------|
| Credit Card | BCA CC, Mandiri CC | Outstanding balance, credit limit |
| Personal Loan | Bank loan, KTA | Remaining balance, monthly payment, interest rate |
| Installment | Phone installment, appliance | Remaining balance, monthly amount, remaining terms |
| Other Debt | Personal borrowing | Outstanding balance |

### What is a "Transaction"?
A financial event. The simplified double-entry model means every transaction involves two sides:

| Transaction Type | Source (Debit) | Destination (Credit) | Cash Flow Impact |
|-----------------|---------------|----------------------|-----------------|
| Income | External (income source) | Account | +Income |
| Expense | Account | External (expense category) | +Expense |
| Transfer | Account A | Account B | None |
| Asset Purchase | Account | Asset Account | None (net worth neutral) |
| Asset Sale | Asset Account | Account | None (net worth neutral before P/L) |
| Liability Payment | Account | Liability | None (net worth neutral) |
| Liability Increase | Liability | Account | None (net worth neutral) |
| Adjustment | System | Account/Asset/Liability | Depends on direction |

---

## 5. Data Ownership

- All financial data is owned by the user
- No data sharing with third parties
- User should be able to export all their data (V1.5 CSV export)
- Data must be backed up regularly
- If the product is discontinued, user must be able to extract all data

---

## 6. Localization Scope

### V1
- UI language: **English** (developer-friendly; easier to maintain)
- Number formatting: **Indonesian** (thousand separator: `.`, decimal separator: `,`)
- Currency display: **Rp** prefix, no decimals for IDR (e.g., `Rp 1.500.000`)
- Date format: **DD/MM/YYYY** or **DD MMM YYYY**
- AI parser: Must understand **Indonesian natural language** even though UI is English

### V1.5+
- Optional Bahasa Indonesia UI translation
- Multi-currency display

---

## 7. Performance Scope

| Metric | V1 Target | V1.5 Target |
|--------|-----------|-------------|
| Lighthouse Performance Score | > 85 | > 90 |
| Lighthouse PWA Score | > 90 | > 95 |
| First Contentful Paint | < 1.5s | < 1.0s |
| Time to Interactive | < 3.0s | < 2.0s |
| Dashboard load (with data) | < 1.5s | < 1.0s |
| Transaction creation | < 500ms | < 300ms |
| Supported concurrent users | 1-5 | 10-50 |

---

## 8. Scope Change Process

Any feature not listed in the V1 scope above must go through this evaluation:

1. **Does it serve a core financial tracking goal?** If no → reject.
2. **Does V1 work without it?** If yes → defer to V1.5 or V2.
3. **Does it add database schema complexity?** If yes → evaluate migration impact.
4. **Does it add external dependencies?** If yes → evaluate reliability risk.
5. **Can it be built in < 1 day?** If no → defer unless it's a core requirement.

Scope changes must be documented and all 12 specification documents updated if affected.
