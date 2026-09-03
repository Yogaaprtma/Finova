# 09 — Design System

> **Document Version**: 1.0  
> **Last Updated**: 2026-08-25  
> **Status**: Approved for Implementation  
> **Cross-references**: [08_UI_UX_SPECIFICATION](./08_UI_UX_SPECIFICATION.md)

---

## 1. Design System: "Obsidian Finance"

A dark-mode-first, data-dense, premium personal finance design system. Built on Tailwind CSS with custom design tokens.

---

## 2. Color Tokens

### 2.1 Semantic Colors

```css
/* Tailwind config extension */
:root {
  /* Brand */
  --color-brand-50: #EEF2FF;
  --color-brand-100: #D8E0FF;
  --color-brand-200: #B4C3FF;
  --color-brand-300: #8DA1FF;
  --color-brand-400: #6B82F6;
  --color-brand-500: #3B63F6;  /* Primary brand */
  --color-brand-600: #2D4FDB;
  --color-brand-700: #233DB8;
  --color-brand-800: #1C3194;
  --color-brand-900: #162571;

  /* Semantic */
  --color-income: #10B981;      /* Emerald 500 */
  --color-income-muted: #065F46; /* Emerald 800 */
  --color-expense: #EF4444;      /* Red 500 */
  --color-expense-muted: #7F1D1D; /* Red 900 */
  --color-transfer: #8B5CF6;     /* Violet 500 */
  --color-warning: #F59E0B;      /* Amber 500 */
  --color-info: #3B82F6;         /* Blue 500 */
}
```

### 2.2 Dark Theme Palette

```css
[data-theme="dark"] {
  --bg-primary: #0F1117;       /* Page background */
  --bg-secondary: #1A1D27;     /* Sidebar, elevated sections */
  --bg-card: #222633;          /* Card surfaces */
  --bg-card-hover: #2A2E3D;   /* Card hover state */
  --bg-input: #1E2130;        /* Input backgrounds */
  --bg-modal: #1A1D27;        /* Modal overlay content */
  
  --border-primary: #2A2D3A;  /* Default borders */
  --border-secondary: #353848; /* Emphasized borders */
  
  --text-primary: #E8E9ED;    /* Primary text */
  --text-secondary: #8B8FA3;  /* Secondary/muted text */
  --text-tertiary: #5B5F73;   /* Disabled/placeholder text */
  --text-inverse: #0F1117;    /* Text on colored backgrounds */
  
  --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.3);
  --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.4);
  --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.5);
}
```

### 2.3 Light Theme Palette

```css
[data-theme="light"] {
  --bg-primary: #F8F9FC;
  --bg-secondary: #FFFFFF;
  --bg-card: #FFFFFF;
  --bg-card-hover: #F3F4F8;
  --bg-input: #F3F4F8;
  --bg-modal: #FFFFFF;
  
  --border-primary: #E5E7EB;
  --border-secondary: #D1D5DB;
  
  --text-primary: #111827;
  --text-secondary: #6B7280;
  --text-tertiary: #9CA3AF;
  --text-inverse: #FFFFFF;
  
  --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
  --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
  --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.12);
}
```

---

## 3. Typography

### 3.1 Font Setup

```css
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

:root {
  --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  --font-mono: 'JetBrains Mono', 'Fira Code', ui-monospace, monospace;
}
```

### 3.2 Type Scale (Tailwind Extensions)

```js
// tailwind.config.ts
fontSize: {
  'display': ['2rem', { lineHeight: '1.1', fontWeight: '700', letterSpacing: '-0.02em' }],     // 32px - net worth
  'title-1': ['1.5rem', { lineHeight: '1.2', fontWeight: '700', letterSpacing: '-0.01em' }],   // 24px - page title
  'title-2': ['1.125rem', { lineHeight: '1.3', fontWeight: '600' }],                           // 18px - section title
  'title-3': ['1rem', { lineHeight: '1.4', fontWeight: '600' }],                               // 16px - card title
  'body': ['0.875rem', { lineHeight: '1.5', fontWeight: '400' }],                              // 14px - body
  'body-medium': ['0.875rem', { lineHeight: '1.5', fontWeight: '500' }],                       // 14px medium
  'caption': ['0.75rem', { lineHeight: '1.4', fontWeight: '400' }],                            // 12px - captions
  'amount-lg': ['1.25rem', { lineHeight: '1.2', fontWeight: '600', letterSpacing: '-0.01em' }],// 20px - medium amounts
  'amount': ['1rem', { lineHeight: '1.4', fontWeight: '600' }],                                // 16px - transaction amounts
  'amount-sm': ['0.875rem', { lineHeight: '1.4', fontWeight: '600' }],                         // 14px - small amounts
}
```

### 3.3 Number Formatting

Financial numbers use **tabular figures** for alignment:

```css
.money {
  font-variant-numeric: tabular-nums;
  font-feature-settings: 'tnum';
}
```

---

## 4. Spacing & Layout

### 4.1 Spacing Scale

Follow Tailwind's default 4px base unit. Key values:

| Token | Value | Usage |
|-------|-------|-------|
| `space-1` | 4px | Tight spacing (between icon and text) |
| `space-2` | 8px | Compact spacing (within components) |
| `space-3` | 12px | Default internal padding |
| `space-4` | 16px | Card padding, list item padding |
| `space-5` | 20px | Section spacing |
| `space-6` | 24px | Page section gaps |
| `space-8` | 32px | Major section separation |

### 4.2 Border Radius

```js
borderRadius: {
  'none': '0',
  'sm': '6px',
  'DEFAULT': '8px',     // Cards, inputs
  'md': '10px',         // Larger cards
  'lg': '12px',         // Modal, bottom sheet
  'xl': '16px',         // Feature cards
  'full': '9999px',     // Circular (avatar, badge)
}
```

### 4.3 Breakpoints

```js
screens: {
  'sm': '640px',
  'md': '768px',   // Tablet
  'lg': '1024px',  // Desktop
  'xl': '1280px',  // Wide desktop
}
```

---

## 5. Component Library

### 5.1 Buttons

#### Primary Button
```html
<button class="btn-primary">
  Save Transaction
</button>
```
```css
.btn-primary {
  background: var(--color-brand-500);
  color: white;
  padding: 10px 20px;
  border-radius: 8px;
  font-weight: 600;
  font-size: 14px;
  transition: background 150ms ease;
}
.btn-primary:hover {
  background: var(--color-brand-600);
}
.btn-primary:active {
  background: var(--color-brand-700);
  transform: scale(0.98);
}
```

#### Secondary Button
```css
.btn-secondary {
  background: transparent;
  color: var(--text-primary);
  border: 1px solid var(--border-primary);
  /* ... same sizing as primary */
}
```

#### Danger Button
```css
.btn-danger {
  background: #DC2626;
  color: white;
  /* ... same sizing as primary */
}
```

#### Ghost Button
```css
.btn-ghost {
  background: transparent;
  color: var(--text-secondary);
  /* No border */
}
```

#### Button Sizes
| Size | Padding | Font Size | Min Height |
|------|---------|-----------|------------|
| sm | 6px 12px | 12px | 32px |
| md (default) | 10px 20px | 14px | 40px |
| lg | 12px 24px | 16px | 48px |

### 5.2 Cards

```css
.card {
  background: var(--bg-card);
  border: 1px solid var(--border-primary);
  border-radius: 8px;
  padding: 16px;
}

.card-elevated {
  background: var(--bg-card);
  border: none;
  border-radius: 10px;
  padding: 20px;
  box-shadow: var(--shadow-md);
}

/* Feature card with subtle gradient (use sparingly) */
.card-feature {
  background: linear-gradient(135deg, var(--bg-card) 0%, var(--color-brand-900) 100%);
  border: 1px solid var(--color-brand-800);
  border-radius: 12px;
  padding: 24px;
}
```

### 5.3 Inputs

```css
.input {
  background: var(--bg-input);
  border: 1px solid var(--border-primary);
  border-radius: 8px;
  padding: 10px 14px;
  color: var(--text-primary);
  font-size: 14px;
  transition: border-color 150ms ease;
}
.input:focus {
  border-color: var(--color-brand-500);
  outline: none;
  box-shadow: 0 0 0 3px rgba(59, 99, 246, 0.15);
}
.input::placeholder {
  color: var(--text-tertiary);
}
```

### 5.4 Badges / Pills

```css
.badge {
  display: inline-flex;
  align-items: center;
  padding: 2px 8px;
  border-radius: 9999px;
  font-size: 12px;
  font-weight: 500;
}
.badge-income {
  background: rgba(16, 185, 129, 0.15);
  color: var(--color-income);
}
.badge-expense {
  background: rgba(239, 68, 68, 0.15);
  color: var(--color-expense);
}
.badge-transfer {
  background: rgba(139, 92, 246, 0.15);
  color: var(--color-transfer);
}
```

### 5.5 Toast / Notification

```css
.toast {
  position: fixed;
  top: 16px;
  right: 16px;
  background: var(--bg-card);
  border: 1px solid var(--border-primary);
  border-radius: 10px;
  padding: 12px 16px;
  box-shadow: var(--shadow-lg);
  z-index: 9999;
  animation: slideIn 200ms ease-out;
}
.toast-success { border-left: 3px solid var(--color-income); }
.toast-error   { border-left: 3px solid var(--color-expense); }

@keyframes slideIn {
  from { transform: translateX(100%); opacity: 0; }
  to   { transform: translateX(0); opacity: 1; }
}
```

### 5.6 Bottom Navigation

```css
.bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background: var(--bg-secondary);
  border-top: 1px solid var(--border-primary);
  display: flex;
  justify-content: space-around;
  align-items: center;
  height: 64px;
  padding-bottom: env(safe-area-inset-bottom); /* iPhone notch */
  z-index: 100;
}
.bottom-nav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2px;
  color: var(--text-tertiary);
  font-size: 10px;
  padding: 8px;
  min-width: 56px;
}
.bottom-nav-item.active {
  color: var(--color-brand-500);
}
/* Center "Add" button */
.bottom-nav-add {
  background: var(--color-brand-500);
  color: white;
  width: 48px;
  height: 48px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: -16px;
  box-shadow: 0 4px 12px rgba(59, 99, 246, 0.4);
}
```

### 5.7 Sidebar (Desktop)

```css
.sidebar {
  position: fixed;
  left: 0;
  top: 0;
  bottom: 0;
  width: 240px;
  background: var(--bg-secondary);
  border-right: 1px solid var(--border-primary);
  padding: 24px 12px;
  display: flex;
  flex-direction: column;
  z-index: 50;
  transition: width 200ms ease;
}
.sidebar.collapsed {
  width: 72px;
}
.sidebar-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  border-radius: 8px;
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 500;
  transition: all 150ms ease;
}
.sidebar-item:hover {
  background: var(--bg-card-hover);
  color: var(--text-primary);
}
.sidebar-item.active {
  background: rgba(59, 99, 246, 0.12);
  color: var(--color-brand-500);
}
```

---

## 6. Chart Styling

### 6.1 Chart.js Theme Configuration

```js
// Default chart colors (dark mode)
const chartDefaults = {
  colors: [
    '#3B82F6', // Blue (Cash)
    '#10B981', // Emerald (Stocks)
    '#8B5CF6', // Violet (Mutual Funds)
    '#F59E0B', // Amber (Crypto)
    '#EC4899', // Pink (Other)
    '#06B6D4', // Cyan
    '#F97316', // Orange
  ],
  grid: {
    color: 'rgba(255, 255, 255, 0.06)', // Very subtle grid
    borderColor: 'rgba(255, 255, 255, 0.1)',
  },
  font: {
    family: 'Inter',
    size: 12,
    color: '#8B8FA3',
  },
  tooltip: {
    backgroundColor: '#222633',
    borderColor: '#353848',
    titleColor: '#E8E9ED',
    bodyColor: '#8B8FA3',
    cornerRadius: 8,
    padding: 12,
  },
};
```

### 6.2 Chart Types and Usage

| Chart | Use | Library |
|-------|-----|---------|
| Donut | Asset allocation | Chart.js (Doughnut) |
| Horizontal bar | Monthly income vs expense | Chart.js (Bar) |
| Line | Net worth trend, cash flow trend | Chart.js (Line) |
| Stacked bar | Expense by category over time | Chart.js (Stacked Bar) |

---

## 7. Animation Tokens

```css
:root {
  --duration-fast: 100ms;
  --duration-normal: 200ms;
  --duration-slow: 300ms;
  --duration-enter: 250ms;
  --duration-exit: 200ms;
  
  --ease-default: cubic-bezier(0.4, 0, 0.2, 1);
  --ease-in: cubic-bezier(0.4, 0, 1, 1);
  --ease-out: cubic-bezier(0, 0, 0.2, 1);
  --ease-spring: cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
```

### Animation Usage Guidelines

| Element | Animation | Duration | Easing |
|---------|-----------|----------|--------|
| Button press | scale(0.98) | fast | ease-default |
| Card hover | background color change | normal | ease-default |
| Modal enter | slide up + fade in | enter | ease-out |
| Modal exit | slide down + fade out | exit | ease-in |
| Toast enter | slide in from right | enter | ease-spring |
| Page transition | fade | normal | ease-default |
| Number count-up | increment from 0 | slow (500ms) | ease-out |

---

## 8. Tailwind CSS Configuration

```ts
// tailwind.config.ts
import type { Config } from 'tailwindcss';

export default {
  content: [
    './resources/js/**/*.{vue,ts}',
    './resources/views/**/*.blade.php',
  ],
  darkMode: ['class', '[data-theme="dark"]'],
  theme: {
    extend: {
      colors: {
        brand: {
          50: '#EEF2FF',
          100: '#D8E0FF',
          200: '#B4C3FF',
          300: '#8DA1FF',
          400: '#6B82F6',
          500: '#3B63F6',
          600: '#2D4FDB',
          700: '#233DB8',
          800: '#1C3194',
          900: '#162571',
        },
        surface: {
          primary: 'var(--bg-primary)',
          secondary: 'var(--bg-secondary)',
          card: 'var(--bg-card)',
          'card-hover': 'var(--bg-card-hover)',
          input: 'var(--bg-input)',
        },
        income: '#10B981',
        expense: '#EF4444',
        transfer: '#8B5CF6',
      },
      fontFamily: {
        sans: ['Inter', '-apple-system', 'BlinkMacSystemFont', 'Segoe UI', 'Roboto', 'sans-serif'],
        mono: ['JetBrains Mono', 'Fira Code', 'ui-monospace', 'monospace'],
      },
      borderRadius: {
        DEFAULT: '8px',
        sm: '6px',
        md: '10px',
        lg: '12px',
        xl: '16px',
      },
      boxShadow: {
        'card': 'var(--shadow-sm)',
        'card-hover': 'var(--shadow-md)',
        'modal': 'var(--shadow-lg)',
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
} satisfies Config;
```

---

## 9. Responsive Behavior Summary

| Component | Mobile (< 768px) | Tablet (768-1023px) | Desktop (≥ 1024px) |
|-----------|-------------------|---------------------|---------------------|
| Navigation | Bottom bar | Collapsed sidebar | Full sidebar |
| Dashboard cards | Stacked (1 col) | 2 columns | 2-3 columns |
| Transaction list | Full width | Full width | Max 800px centered |
| Quick add | Bottom sheet modal | Bottom sheet modal | Side panel or modal |
| Charts | Full width, shorter | Full width | Fixed width within cards |
| Sidebar | Hidden | 72px (icons only) | 240px (full) |
| Content padding | 16px | 24px | 32px |

---

## 10. Implementation Notes for Antigravity

1. **Theme switching**: Use `data-theme` attribute on `<html>` element, toggled via Vue composable (`useTheme`)
2. **CSS custom properties**: Define in `resources/css/app.css` before Tailwind imports
3. **Component approach**: Build Vue components that reference design tokens, not hardcoded colors
4. **Mobile-first**: Write mobile styles first, use `md:` and `lg:` prefixes for larger screens
5. **Transitions**: Use Vue's `<Transition>` component with named transition classes
6. **Charts**: Configure Chart.js defaults once in a composable (`useChart`), not per-chart
7. **Icons**: Install `lucide-vue-next` package and use as Vue components
8. **Safe area**: Always use `env(safe-area-inset-bottom)` on fixed bottom elements for iPhone notch support
