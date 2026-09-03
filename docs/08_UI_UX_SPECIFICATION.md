# 08 — UI/UX Specification

> **Document Version**: 1.0  
> **Last Updated**: 2026-08-25  
> **Status**: Approved for Implementation  
> **Cross-references**: [09_DESIGN_SYSTEM](./09_DESIGN_SYSTEM.md), [03_FEATURE_SPECIFICATION](./03_FEATURE_SPECIFICATION.md)

---

## 1. Design Philosophy

### Name: "Obsidian Finance"

A premium, dark-mode-first personal wealth management aesthetic. Clean, information-dense, and professional — like a Bloomberg terminal refined for personal use.

### Core Principles

| Principle | Description |
|-----------|-------------|
| **Data First** | Financial data is the hero. Typography and numbers should be prominent, not decorative elements. |
| **Calm Confidence** | The UI should feel calm and trustworthy. No flashy animations or excessive color. |
| **Mobile Native Feel** | On mobile, the app should feel like a native app — not a scaled-down website. |
| **Progressive Disclosure** | Show essential information first, reveal details on demand. |
| **Scannable** | Users should be able to glance at the dashboard and understand their financial health in 3 seconds. |

### Anti-Patterns to Avoid

- ❌ Excessive glassmorphism (subtle only)
- ❌ Rainbow gradients
- ❌ Overly rounded cards (use 8-12px radius, not 24px)
- ❌ Neon accent colors
- ❌ Tiny text crammed with data
- ❌ Generic banking app look
- ❌ Gamification elements (badges, confetti)
- ❌ "Flat" look with no visual hierarchy

---

## 2. Layout Architecture

### 2.1 Mobile Layout (< 768px)

```
┌─────────────────────────┐
│   Status Bar (native)    │
├─────────────────────────┤
│   Top Bar               │
│   [≡ Menu]  Title  [🔔] │
├─────────────────────────┤
│                         │
│                         │
│     Content Area        │
│     (scrollable)        │
│                         │
│                         │
│                         │
│                         │
│              [+ FAB]    │
├─────────────────────────┤
│  Bottom Navigation      │
│  🏠  📋  ➕  📊  ⚙️    │
│  Home Trans Add Port More│
└─────────────────────────┘
```

- **Top bar**: Title, optional back button, notification bell
- **Content area**: Full-width, scrollable content
- **FAB (Floating Action Button)**: Quick add transaction (positioned above bottom nav)
- **Bottom navigation**: 5 items max

### 2.2 Desktop Layout (≥ 1024px)

```
┌──────────────┬──────────────────────────────────────┐
│              │  Top Bar                              │
│              │  🔍 Search...        User ▾  🌙/☀️   │
│  Sidebar     ├──────────────────────────────────────┤
│              │                                      │
│  🏠 Dashboard│     Content Area                     │
│  📋 Trans.   │     (max-width: 1200px)              │
│  💳 Accounts │     (centered)                       │
│  📊 Portfolio│                                      │
│  📈 Reports  │                                      │
│  🏷️ Categ.  │                                      │
│  ⚙️ Settings │                                      │
│              │                                      │
│              │                                      │
│              │                                      │
└──────────────┴──────────────────────────────────────┘
```

- **Sidebar**: Fixed, collapsible (icon-only mode)
- **Top bar**: Search bar, user menu, theme toggle
- **Content area**: Max-width 1200px, centered with padding
- **No FAB on desktop**: Use a "New Transaction" button in content area

### 2.3 Tablet Layout (768px - 1023px)

- Collapsed sidebar (icon-only) + content area
- Or: mobile layout with wider cards

---

## 3. Navigation Structure

### 3.1 Mobile Bottom Navigation

| Position | Icon | Label | Page |
|----------|------|-------|------|
| 1 | 🏠 | Home | Dashboard |
| 2 | 📋 | Transactions | Transaction list |
| 3 | ➕ | Add | Quick add modal (center, elevated) |
| 4 | 📊 | Portfolio | Assets, investments, liabilities |
| 5 | ☰ | More | Settings, categories, reports, accounts |

The center "Add" button is visually distinct (elevated, accent color, slightly larger).

### 3.2 Desktop Sidebar Navigation

| Icon | Label | Page |
|------|-------|------|
| 🏠 | Dashboard | Main dashboard |
| 📋 | Transactions | Transaction list with search/filter |
| 💳 | Accounts | Cash, bank, e-wallet accounts |
| 📊 | Portfolio | Assets + investments + liabilities |
| 📈 | Reports | Financial reports |
| 🏷️ | Categories | Category management |
| ⚙️ | Settings | User settings, integrations |

**Sidebar footer**: User avatar, name, logout

### 3.3 "More" Menu (Mobile)

When user taps "More" in bottom nav:
- Accounts
- Reports
- Categories
- Settings
- About

---

## 4. Page Specifications

### 4.1 Dashboard

**Purpose**: At-a-glance financial health overview.

**Mobile Layout** (single column, scrollable):

1. **Net Worth Card** (full width)
   - Large number: `Rp 125.450.000`
   - Change indicator: `▲ +2.3% vs last month` (green) or `▼ -1.5%` (red)
   - Subtle background gradient (dark theme: dark blue-gray to charcoal)

2. **Quick Stats Row** (2 cards, side by side)
   - Total Assets: `Rp 150.000.000`
   - Total Liabilities: `Rp 24.550.000`

3. **Monthly Cash Flow Card**
   - Income bar + amount
   - Expense bar + amount
   - Net: `Rp 4.800.000` (savings rate %)
   - Period selector: This Month / Last Month

4. **Asset Allocation Chart**
   - Donut chart with legend
   - Segments: Cash, Stocks, Mutual Funds, Crypto, Other

5. **Recent Transactions**
   - Last 10 transactions
   - Each: icon, description, amount (green for income, red for expense), date
   - "View All →" link

**Desktop Layout** (2-3 column grid):
- Net worth spans full width
- Quick stats + cash flow in first row
- Asset allocation + recent transactions in second row
- More breathing room, larger charts

### 4.2 Transaction List

**Purpose**: Browse, search, and filter all transactions.

**Header**:
- Search input
- Filter chips: Type, Category, Account, Date Range
- "New Transaction" button (desktop) / FAB triggers modal (mobile)

**List**:
- Grouped by date (Today, Yesterday, Earlier this week, etc.)
- Each transaction row:
  - Category icon (colored circle)
  - Description (bold)
  - Category name (muted)
  - Amount (right-aligned, colored: green income, red expense, neutral transfer)
  - Account name (small, muted)
- Pull-to-refresh (mobile)
- Infinite scroll pagination

**Transaction Detail** (click/tap to expand or navigate):
- Full details: type, amount, category, account, date, description, notes, source
- Edit button, delete button
- If source is AI: show "AI parsed" badge with confidence

### 4.3 Transaction Create/Edit

**Modal (mobile) / Side panel (desktop)**:

Step 1: Select type (4 large buttons):
- 💸 Expense | 💰 Income | 🔄 Transfer | ⋯ More

Step 2 (for Expense/Income):
- **Amount**: Large input with currency prefix, numeric keyboard (mobile)
- **Category**: Grid of category icons (scrollable), search
- **Account**: Dropdown (default account pre-selected)
- **Description**: Text input
- **Date**: Date picker (defaults to today)
- **Notes**: Collapsible textarea
- **[Save]** button

Step 2 (for Transfer):
- Amount, From account, To account, Description, Date

Step 2 (for Asset Purchase/Sale):
- Amount, Account, Asset account, Quantity, Price per unit, Fees, Date

**UX Goal**: Record a simple expense in ≤ 3 taps + typing.

### 4.4 Account List

- Cards for each account
- Each card: account name, type icon, balance (large), recent activity count
- Color-coded by type (or user-chosen color)
- Tap to view account detail (balance history, transaction list filtered to account)
- "Add Account" button

### 4.5 Portfolio Page

**Three tabs/sections**:

1. **Assets** (investments with quantity tracking)
   - Summary: total investment value, total unrealized P/L
   - List grouped by type (Stocks, Mutual Funds, Crypto)
   - Each: name, ticker, quantity, current price, current value, P/L (amount + %)
   
2. **Other Assets** (value-only: property, vehicle)
   - List with name, estimated value
   
3. **Liabilities**
   - Summary: total debt
   - List: name, type, current balance, credit limit (if card), next due date
   - Color coding: near due date = amber, overdue = red

### 4.6 Reports Page

- Report type selector (tabs or cards)
- Date range selector
- Chart + data table for each report type
- Reports: Income/Expense, Cash Flow, Expense by Category, Net Worth Trend, Asset Allocation

### 4.7 Category Management

- Tabs: Income | Expense
- List: icon, name, transaction count (this month)
- Drag to reorder
- Tap to edit (name, icon, color)
- "Add Category" button
- Expand to show subcategories
- Swipe to deactivate (mobile)

### 4.8 Settings

- Profile (name, email, password change)
- Display (theme, date format, start of month)
- Accounts (manage accounts — duplicate of Accounts page for discoverability)
- Integrations (V1.5: WhatsApp, AI provider; V2: Indodax, etc.)
- Data (export CSV, V2: import)
- About (version, credits)

---

## 5. Component Specifications

### 5.1 MoneyDisplay Component

Formats and displays monetary amounts consistently.

```
Props:
  - amount: number (integer, smallest unit)
  - currency: string (default: 'IDR')
  - showSign: boolean (default: false) — show +/- prefix
  - colorize: boolean (default: false) — green for positive, red for negative
  - size: 'sm' | 'md' | 'lg' | 'xl'

Examples:
  Rp 1.500.000     (neutral)
  +Rp 8.000.000    (green, income)
  -Rp 350.000      (red, expense)
```

### 5.2 MoneyInput Component

Numeric input optimized for money entry.

```
Props:
  - modelValue: number
  - currency: string (default: 'IDR')
  - placeholder: string

Behavior:
  - Shows currency prefix
  - Auto-formats with thousand separators as user types
  - Mobile: triggers numeric keyboard (inputmode="numeric")
  - Strips formatting before emitting value
  - Emits integer value (not formatted string)
```

### 5.3 TransactionRow Component

Single transaction in a list.

```
Props:
  - transaction: TransactionData

Layout:
  [Icon] [Description     ] [Amount]
         [Category · Account] [Date  ]
```

### 5.4 CategoryPicker Component

Grid/list of categories for selection during transaction creation.

```
Props:
  - type: 'income' | 'expense'
  - modelValue: string | null (category ID)

Layout (mobile): 4-column grid of colored circle icons with labels
Layout (desktop): Scrollable list with icons
```

### 5.5 DateRangePicker Component

For report filtering.

```
Presets: Today, This Week, This Month, Last Month, Last 3 Months, Last 6 Months, YTD, Last Year, All Time, Custom
Custom: Two date inputs (from, to)
```

### 5.6 EmptyState Component

Shown when a list has no data.

```
Props:
  - icon: string
  - title: string
  - description: string
  - actionLabel: string (optional button)
  - actionRoute: string

Example:
  [Illustration]
  "No transactions yet"
  "Record your first transaction to get started"
  [+ Add Transaction]
```

---

## 6. Interaction Patterns

### 6.1 Touch Gestures (Mobile)
- **Pull to refresh**: Transaction list, dashboard
- **Swipe left on transaction**: Quick actions (edit, delete)
- **Tap and hold on transaction**: Context menu (edit, delete, duplicate)
- **Tap category icon**: Select category (during creation)

### 6.2 Animations
- **Page transitions**: Subtle fade/slide (Inertia's built-in transitions)
- **Card appearance**: Staggered fade-in on dashboard load
- **Number counting**: Net worth and totals animate from 0 to value on page load (once)
- **Chart rendering**: Bars/lines draw in sequence
- **Modal**: Slide up from bottom (mobile), fade in (desktop)
- **Toast**: Slide in from top, auto-dismiss after 3 seconds

### 6.3 Feedback
- **Success**: Green toast with checkmark ("Transaction saved")
- **Error**: Red toast with X ("Failed to save. Please try again.")
- **Loading**: Skeleton screens (not spinners) for data loading
- **Empty**: Illustrated empty states with action buttons
- **Confirmation**: "Are you sure?" dialog for destructive actions (delete)

---

## 7. Responsive Breakpoints

| Name | Min Width | Layout |
|------|-----------|--------|
| Mobile | 0px | Single column, bottom nav, FAB |
| Tablet | 768px | Collapsed sidebar + content |
| Desktop | 1024px | Full sidebar + content |
| Wide | 1280px | Full sidebar + wider content area |

---

## 8. Dark Mode / Light Mode

### Dark Mode (Default)
- Background: Deep charcoal (`#0F1117`)
- Surface: Slightly lighter (`#1A1D27`)
- Cards: Subtle elevation (`#222633`)
- Text primary: Off-white (`#E8E9ED`)
- Text secondary: Muted gray (`#8B8FA3`)
- Accent: Cool blue (`#3B82F6`)
- Income green: `#10B981`
- Expense red: `#EF4444`
- Borders: Very subtle (`#2A2D3A`)

### Light Mode
- Background: Warm white (`#F8F9FC`)
- Surface: White (`#FFFFFF`)
- Cards: White with subtle shadow
- Text primary: Dark gray (`#111827`)
- Text secondary: Medium gray (`#6B7280`)
- Accent: Same cool blue (`#3B82F6`)
- Income green: `#059669`
- Expense red: `#DC2626`
- Borders: Light gray (`#E5E7EB`)

---

## 9. Typography

### Font Stack
- **Primary**: `Inter` (Google Fonts) — clean, modern, excellent number rendering
- **Monospace** (for numbers): `JetBrains Mono` or `Inter` with tabular numbers
- **Fallback**: `-apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif`

### Scale

| Use | Size | Weight | Line Height |
|-----|------|--------|-------------|
| Page title | 24px / 1.5rem | 700 | 1.2 |
| Section title | 18px / 1.125rem | 600 | 1.3 |
| Card title | 16px / 1rem | 600 | 1.4 |
| Body | 14px / 0.875rem | 400 | 1.5 |
| Small / Caption | 12px / 0.75rem | 400 | 1.4 |
| Large number (net worth) | 32px / 2rem | 700 | 1.1 |
| Medium number (totals) | 20px / 1.25rem | 600 | 1.2 |
| Transaction amount | 16px / 1rem | 600 | 1.4 |

---

## 10. Iconography

Use **Lucide Icons** (open source, consistent style, good Vue support).

- Consistent 24×24 base size
- 1.5px stroke weight
- Filled variants for navigation (active state)
- Outline for inactive/secondary

### Key Icons

| Use | Lucide Icon |
|-----|-------------|
| Dashboard | `layout-dashboard` |
| Transactions | `receipt` |
| Add | `plus` |
| Portfolio | `pie-chart` |
| Settings | `settings` |
| Income | `trending-up` |
| Expense | `trending-down` |
| Transfer | `arrow-left-right` |
| Bank | `landmark` |
| Cash | `wallet` |
| E-wallet | `smartphone` |
| Credit Card | `credit-card` |
| Stocks | `bar-chart-2` |
| Crypto | `bitcoin` |
| Search | `search` |
| Filter | `sliders-horizontal` |
| Edit | `pencil` |
| Delete | `trash-2` |
| Close | `x` |
| Check | `check` |
| Calendar | `calendar` |
| Moon (dark) | `moon` |
| Sun (light) | `sun` |

---

## 11. Accessibility

### Minimum Requirements (V1)
- Color contrast ratio ≥ 4.5:1 for text
- All interactive elements have focus states
- Form labels are properly associated with inputs
- Buttons have minimum 44×44px touch target (mobile)
- Screen reader support for key financial data
- Keyboard navigable on desktop
- `aria-label` on icon-only buttons
- Error messages associated with form fields

### Enhanced (V1.5+)
- Reduced motion preference respected
- High contrast mode
- Screen reader announcements for dynamic updates (toasts, balances)
