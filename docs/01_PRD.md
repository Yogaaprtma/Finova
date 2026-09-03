# 01 — Product Requirements Document (PRD)

> **Document Version**: 1.0  
> **Last Updated**: 2026-08-25  
> **Status**: Approved for Implementation  
> **Cross-references**: [02_PRODUCT_SCOPE](./02_PRODUCT_SCOPE.md), [03_FEATURE_SPECIFICATION](./03_FEATURE_SPECIFICATION.md)

---

## 1. Executive Summary

**Product Name**: To be finalized (working title: **Finova**)

**Product Type**: Web-based Personal Finance Management application, built as a Progressive Web App (PWA) for mobile-native experience.

**Purpose**: Provide a single hub for tracking personal finances — cash flow (income/expenses), assets (investments, bank accounts, e-wallets), and liabilities (debts, credit cards, loans) — with accurate net worth monitoring and an AI-powered natural language transaction input system.

**Target User**: Initially the product owner (single-user). Architecture must not prevent future multi-user expansion.

**Core Problem**: Personal financial data is fragmented across multiple platforms (banks, e-wallets, brokerages, crypto exchanges). No single tool provides:
- Unified net worth visibility
- Quick daily transaction recording
- Investment portfolio tracking
- Natural language input (Indonesian)
- All in a mobile-friendly, installable PWA

---

## 2. Product Vision

> A personal financial operating system that makes recording transactions effortless, provides real-time visibility into net worth, and treats every financial action — income, expense, transfer, investment, debt — with logical consistency.

### Key Principles

| Principle | Description |
|-----------|-------------|
| **Accuracy First** | Financial data must be logically consistent. No floating-point errors. No incorrect balance calculations. |
| **Effortless Input** | Recording a transaction should take < 10 seconds on mobile. |
| **Unified View** | One dashboard showing complete financial health. |
| **Graceful Degradation** | App must be fully functional with manual entry alone. AI and integrations are enhancements, not dependencies. |
| **Simplicity** | Personal finance tool, not enterprise ERP. Avoid unnecessary complexity. |
| **Security** | Financial data is highly sensitive. Secure by default. |

---

## 3. User Personas

### Primary: Product Owner (Solo User)

- **Demographics**: Indonesian professional, tech-savvy, manages personal finances across multiple platforms
- **Financial Profile**:
  - Multiple bank accounts and e-wallets
  - Active stock investor (BMRI, BBRI via Stockbit)
  - Mutual fund investor (RDPU, RDPT via Bibit)
  - Crypto holder (BTC, ETH via Indodax)
  - Uses credit cards, has installment payments
- **Pain Points**:
  - Cannot see total net worth in one place
  - Forgets to record daily expenses
  - Manual spreadsheet tracking is tedious
  - No easy way to see investment performance across platforms
- **Behavior**:
  - Prefers mobile usage for daily recording
  - Wants quick input without navigating complex forms
  - Reviews financial dashboard weekly
  - Adjusts investment portfolio monthly

### Future: Additional Users (V2+)

- Family members with shared/separate accounts
- Friends who want the same tool
- Potential SaaS users (if product is commercialized)

---

## 4. Core Use Cases

### UC-01: Record Daily Expense
**Actor**: User  
**Flow**: Open app → Tap "+" → Type "beli makan siang 25rb" → App parses and creates expense transaction → Confirm → Done  
**Success Criteria**: Transaction created in < 10 seconds

### UC-02: Record Income
**Actor**: User  
**Flow**: Open app → Tap "+" → Select "Income" → Enter amount, category, description → Save  
**Success Criteria**: Income recorded, cash account balance updated

### UC-03: Transfer Between Accounts
**Actor**: User  
**Flow**: Transactions → New Transfer → Select source/destination → Enter amount → Save  
**Success Criteria**: Source decreases, destination increases, no income/expense impact

### UC-04: Check Net Worth
**Actor**: User  
**Flow**: Open app → Dashboard displays net worth = Total Assets - Total Liabilities  
**Success Criteria**: Net worth is mathematically consistent with all account balances

### UC-05: Track Investment Portfolio
**Actor**: User  
**Flow**: Portfolio → View all investments → See quantity, avg price, current value, unrealized P/L  
**Success Criteria**: Portfolio values reflect manual entries and any available price updates

### UC-06: Record Asset Purchase
**Actor**: User  
**Flow**: New Transaction → Asset Purchase → Select asset account → Enter quantity, price → Save  
**Success Criteria**: Cash decreases, asset value increases, net worth approximately unchanged

### UC-07: Record Liability Payment
**Actor**: User  
**Flow**: New Transaction → Liability Payment → Select liability → Enter amount → Save  
**Success Criteria**: Cash decreases, liability balance decreases, net worth approximately unchanged

### UC-08: View Monthly Cash Flow
**Actor**: User  
**Flow**: Dashboard → Cash Flow section → See total income, total expense, net cash flow for current month  
**Success Criteria**: Accurate aggregation excluding transfers

### UC-09: Search Transactions
**Actor**: User  
**Flow**: Transactions → Search "nasi kuning" → Filter by date range → View results  
**Success Criteria**: Returns all matching transactions

### UC-10: AI Quick Input (V1.5)
**Actor**: User  
**Flow**: Quick Input box → Type "tadi beli kopi 18rb" → AI parses → Shows structured preview → User confirms → Transaction saved  
**Success Criteria**: AI correctly extracts amount, category, description with >90% accuracy for common transactions

---

## 5. Product Requirements

### 5.1 Functional Requirements

| ID | Requirement | Priority | Version |
|----|-------------|----------|---------|
| FR-01 | User can create, read, update, delete accounts (cash, bank, e-wallet) | Must | V1 |
| FR-02 | User can record income transactions | Must | V1 |
| FR-03 | User can record expense transactions | Must | V1 |
| FR-04 | User can record transfers between accounts | Must | V1 |
| FR-05 | User can record asset purchases | Must | V1 |
| FR-06 | User can record asset sales | Must | V1 |
| FR-07 | User can record liability payments | Must | V1 |
| FR-08 | User can manage investment assets manually | Must | V1 |
| FR-09 | User can manage liabilities manually | Must | V1 |
| FR-10 | Dashboard shows net worth, cash flow, recent transactions | Must | V1 |
| FR-11 | User can manage categories and subcategories | Must | V1 |
| FR-12 | User can search and filter transactions | Must | V1 |
| FR-13 | System generates monthly reports (income, expense, cash flow) | Must | V1 |
| FR-14 | App is installable as PWA | Must | V1 |
| FR-15 | AI natural language transaction parsing (web input) | Should | V1.5 |
| FR-16 | WhatsApp transaction recording | Should | V1.5 |
| FR-17 | Monthly/category budgets with tracking | Should | V1.5 |
| FR-18 | Recurring transaction templates | Should | V1.5 |
| FR-19 | CSV data export | Should | V1.5 |
| FR-20 | Financial goals tracking | Could | V2 |
| FR-21 | AI financial insights | Could | V2 |
| FR-22 | Indodax API integration | Could | V2 |
| FR-23 | CSV import (Bibit/Stockbit statements) | Could | V2 |
| FR-24 | Multi-currency conversion | Could | V2 |
| FR-25 | Receipt/attachment support | Could | V2 |
| FR-26 | Offline transaction entry with sync | Could | V2 |

### 5.2 Non-Functional Requirements

| ID | Requirement | Target |
|----|-------------|--------|
| NFR-01 | Page load time | < 2 seconds on 4G mobile |
| NFR-02 | Transaction creation response time | < 500ms |
| NFR-03 | Dashboard data load | < 1 second |
| NFR-04 | Mobile Lighthouse PWA score | > 90 |
| NFR-05 | Financial calculation accuracy | 100% (integer arithmetic, no rounding errors) |
| NFR-06 | Uptime (production) | 99.5% |
| NFR-07 | Data backup frequency | Daily |
| NFR-08 | Support dark mode and light mode | Required |
| NFR-09 | Mobile-first responsive design | Required |
| NFR-10 | HTTPS enforced | Required |

---

## 6. Constraints

| Constraint | Impact |
|------------|--------|
| Solo developer/maintainer | Limits V1 scope; must prioritize ruthlessly |
| No Bibit/Stockbit public API | Investment data must be manual in V1 |
| Indonesian language support required | AI parser must handle Bahasa Indonesia, slang, abbreviations |
| Budget-conscious deployment | VPS at $5-12/month; no expensive managed services |
| Financial data sensitivity | Cannot use unreliable free hosting for production |
| PWA limitations | No push notifications on iOS Safari (limited); no full offline DB |

---

## 7. Success Metrics

| Metric | Target | Measurement |
|--------|--------|-------------|
| Daily transaction recording | User records ≥3 transactions/day within first month | Transaction count per day |
| Financial overview accuracy | Net worth matches manual calculation | Periodic reconciliation |
| Mobile usability | Transaction can be recorded in < 10 seconds | Manual timing test |
| AI parsing accuracy (V1.5) | > 90% correct extraction for common transactions | AI log analysis |
| App reliability | Zero data loss incidents | Monitoring + audit logs |

---

## 8. Assumptions

1. User has a modern smartphone with Chrome/Safari browser
2. User has reliable internet access (no offline-first requirement for V1)
3. User understands basic financial concepts (income, expense, asset, liability)
4. Indonesian Rupiah (IDR) is the primary currency
5. User is willing to manually enter investment data for V1
6. Single-user system for V1 (no sharing, no multi-tenancy)

---

## 9. Risks

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|------------|
| AI parser produces incorrect transactions | High | High | Confidence threshold + mandatory confirmation for uncertain parses |
| WhatsApp API policy changes | Medium | High | Abstract WhatsApp behind adapter; core app works without it |
| Investment price data unavailable | Medium | Medium | Show "last updated" timestamps; allow manual price override |
| Scope creep into enterprise features | High | Medium | Strict V1 scope enforcement; no features beyond approved list |
| Data loss on budget hosting | Low | Critical | Automated daily backups; separate backup storage |
| User abandons due to tedious input | Medium | High | AI quick input (V1.5); mobile-optimized forms (V1) |

---

## 10. Glossary

| Term | Definition |
|------|-----------|
| **Account** | A container for money (bank account, e-wallet, cash) |
| **Asset** | Something with financial value owned by the user |
| **Asset Account** | A tracked investment position (e.g., "BMRI in Stockbit") |
| **Liability** | A financial obligation owed by the user |
| **Transaction** | A financial event that moves money between accounts or changes balances |
| **Transfer** | Money moved between the user's own accounts (not income/expense) |
| **Net Worth** | Total Assets minus Total Liabilities |
| **Cash Flow** | Net of income minus expenses over a period (excludes transfers) |
| **Double-Entry (Simplified)** | Every transaction has a source and destination, ensuring balance consistency |
| **PWA** | Progressive Web App — installable web application with native-like behavior |
