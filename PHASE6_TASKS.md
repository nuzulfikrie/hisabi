# Phase 6: Financial Intelligence & Dashboard Enhancements

## Instructions for LLM Agent

- Pick **one task at a time**, starting from the lowest numbered uncompleted task
- Each task is self-contained with all context needed to implement
- After completing a task, mark it `[x]` and move to the next
- Run the relevant tests after each task: `php artisan test tests/Feature/<path>`
- Do NOT modify unrelated files or refactor existing code
- Follow existing patterns in the codebase (Domain structure, Pest v4 tests, Inertia v2 + React + TypeScript)
- Use `withoutVite()` in feature tests (already configured in `tests/Pest.php`)
- Docker/Sail is broken — run commands with local PHP directly (e.g., `php artisan test`)

---

## Project Context

| Item | Detail |
|------|--------|
| Framework | Laravel 12 + Inertia v2 + React 18 + TypeScript + Tailwind v4 |
| Pages dir | `resources/js/pages/` (lowercase p) |
| Domains dir | `app/Domains/` (Transaction, Budget, Brand, Category, Sms, User, Metrics) |
| Metrics dir | `app/Domains/Metrics/Metrics/` (one class per metric) |
| Routes | `routes/web/*.php` (loaded via `require_all_in()`) |
| API routes | `routes/api.php` (versioned `/api/v1/`) |
| Test runner | `php artisan test` (Pest v4, no Sail) |
| Existing tests | 480 tests, 1675 assertions |
| Models | User, Transaction, Category, Brand, Budget, Sms, Setting, UserSetting, TelegramTransaction |

---

## Task List

### Section A: New Metrics (Backend)

#### A1 — Cash Flow Metric
- [ ] **Create `app/Domains/Metrics/Metrics/CashFlowMetric.php`**
- **What:** Calculate net cash flow (Income - Expenses) for a given date range, including comparison to previous period
- **Pattern:** Follow `TotalIncomeMetric.php` and `TotalExpensesMetric.php` — extend `Metric` base class
- **Return data:** `{ current: { income, expenses, net }, previous: { income, expenses, net }, change_percentage }`
- **Register:** Add API route in `routes/api.php` under metrics group
- **Test:** Create `tests/Feature/Metrics/CashFlowMetricTest.php`
  - Test positive cash flow (income > expenses)
  - Test negative cash flow (expenses > income)
  - Test zero transactions returns zeroes
  - Test date range filtering works
  - Test previous period comparison is correct
  - Test unauthenticated returns 401

---

#### A2 — Savings Rate Metric
- [ ] **Create `app/Domains/Metrics/Metrics/SavingsRateMetric.php`**
- **What:** Calculate savings rate as `(Total Savings / Total Income) * 100` for a date range
- **Return data:** `{ rate: float, target: 20, gap_amount: float, status: 'excellent'|'good'|'warning'|'danger', income: float, savings: float }`
- **Status rules:** excellent >= 20%, good 10-19%, warning 5-9%, danger < 5%
- **Edge case:** If income is 0, rate is 0 and status is `danger`
- **Register:** Add API route in `routes/api.php` under metrics group
- **Test:** Create `tests/Feature/Metrics/SavingsRateMetricTest.php`
  - Test excellent rate (>= 20%)
  - Test good rate (10-19%)
  - Test warning rate (5-9%)
  - Test danger rate (< 5%)
  - Test zero income edge case
  - Test date range filtering

---

#### A3 — Emergency Fund Metric
- [ ] **Create `app/Domains/Metrics/Metrics/EmergencyFundMetric.php`**
- **What:** Calculate months of expenses covered by savings: `Total Savings / Average Monthly Expenses`
- **Return data:** `{ months_covered: float, avg_monthly_expenses: float, total_savings: float, status: 'safe'|'warning'|'danger', target_3mo_gap: float, target_6mo_gap: float }`
- **Status rules:** safe > 6 months, warning 3-6 months, danger < 3 months
- **Uses:** `TotalSavingsMetric` and `TotalExpensesMetric` data for computation
- **Edge case:** If no expenses, months_covered = infinity → cap at 99
- **Register:** Add API route in `routes/api.php` under metrics group
- **Test:** Create `tests/Feature/Metrics/EmergencyFundMetricTest.php`
  - Test safe status (> 6 months)
  - Test warning status (3-6 months)
  - Test danger status (< 3 months)
  - Test zero expenses edge case
  - Test zero savings returns 0 months

---

#### A4 — Financial Health Score Metric
- [ ] **Create `app/Domains/Metrics/Metrics/FinancialHealthScoreMetric.php`**
- **What:** Composite score (0-100) based on weighted factors
- **Scoring formula:**
  - Cash Flow Score (25 pts): positive = 25, break-even = 15, negative = 0
  - Savings Rate Score (25 pts): >= 20% = 25, 10-19% = 15, 5-9% = 8, < 5% = 0
  - Emergency Fund Score (25 pts): > 6mo = 25, 3-6mo = 15, 1-3mo = 8, < 1mo = 0
  - Budget Compliance Score (25 pts): all under = 25, 1 over = 18, 2+ over = 10, no budgets = 12
- **Return data:** `{ score: int, status: string, breakdown: { cash_flow: int, savings_rate: int, emergency_fund: int, budget_compliance: int } }`
- **Status:** Excellent (90+), Good (70-89), Fair (50-69), Needs Attention (< 50)
- **Register:** Add API route in `routes/api.php` under metrics group
- **Test:** Create `tests/Feature/Metrics/FinancialHealthScoreMetricTest.php`
  - Test excellent score scenario
  - Test poor score scenario
  - Test each component contributes correctly
  - Test with no data returns a sensible default

---

#### A5 — Spending Alerts Metric
- [ ] **Create `app/Domains/Metrics/Metrics/SpendingAlertsMetric.php`**
- **What:** Detect spending anomalies and budget warnings
- **Alert types:**
  - `category_spike` — category spending increased > 30% vs previous period
  - `budget_warning` — budget at 80%+ utilization
  - `budget_exceeded` — budget over 100%
  - `category_decrease` — positive: category spending decreased > 20%
- **Return data:** `{ alerts: [{ type, severity: 'critical'|'warning'|'positive', title, message, amount?, category?, budget? }], count: int }`
- **Max 10 alerts**, sorted by severity (critical first)
- **Register:** Add API route in `routes/api.php` under metrics group
- **Test:** Create `tests/Feature/Metrics/SpendingAlertsMetricTest.php`
  - Test category spike detection
  - Test budget warning at 80%
  - Test budget exceeded
  - Test positive decrease alert
  - Test max 10 alert cap
  - Test no alerts when everything is normal

---

#### A6 — Top Expenses Metric
- [ ] **Create `app/Domains/Metrics/Metrics/TopExpensesMetric.php`**
- **What:** Return top 10 individual expense transactions by amount
- **Return data:** `{ transactions: [{ id, amount, brand_name, category_name, date, note }] }`
- **Scope:** Only expense-type transactions, filtered by date range and user
- **Register:** Add API route in `routes/api.php` under metrics group
- **Test:** Create `tests/Feature/Metrics/TopExpensesMetricTest.php`
  - Test returns max 10 transactions
  - Test ordered by amount descending
  - Test only returns expenses (not income/savings)
  - Test date range filtering
  - Test user scoping (can't see other users' transactions)

---

#### A7 — Recurring Expenses Metric
- [ ] **Create `app/Domains/Metrics/Metrics/RecurringExpensesMetric.php`**
- **What:** Detect recurring expenses by finding transactions with the same brand that appear 3+ times with roughly monthly intervals (25-35 day gaps)
- **Algorithm:**
  1. Group expense transactions by `brand_id`
  2. For each brand with 3+ transactions, check if intervals between consecutive transactions are 25-35 days
  3. If >= 60% of intervals match, classify as recurring
- **Return data:** `{ recurring: [{ brand_id, brand_name, avg_amount, frequency: 'monthly', last_date, transaction_count, is_stale: bool }], total_monthly_cost: float }`
- `is_stale`: true if last transaction > 60 days ago
- **Register:** Add API route in `routes/api.php` under metrics group
- **Test:** Create `tests/Feature/Metrics/RecurringExpensesMetricTest.php`
  - Test detects monthly recurring (3 transactions ~30 days apart)
  - Test ignores non-recurring (random intervals)
  - Test stale detection (last > 60 days ago)
  - Test total monthly cost calculation
  - Test requires minimum 3 transactions

---

#### A8 — Cash Runway Metric
- [ ] **Create `app/Domains/Metrics/Metrics/CashRunwayMetric.php`**
- **What:** Calculate how many months current cash will last: `(Total Cash + Total Savings) / Avg Monthly Expenses`
- **Return data:** `{ months: float, monthly_burn_rate: float, available_funds: float, status: 'safe'|'warning'|'danger' }`
- **Status:** safe > 6, warning 3-6, danger < 3
- **Register:** Add API route in `routes/api.php` under metrics group
- **Test:** Create `tests/Feature/Metrics/CashRunwayMetricTest.php`

---

#### A9 — Income Stability Metric
- [ ] **Create `app/Domains/Metrics/Metrics/IncomeStabilityMetric.php`**
- **What:** Calculate coefficient of variation of monthly income over last 12 months: `(StdDev / Mean) * 100`
- **Return data:** `{ cv: float, status: 'stable'|'moderate'|'variable'|'highly_variable', min: float, max: float, average: float, monthly_values: float[] }`
- **Status:** stable CV < 15%, moderate 15-30%, variable 30-50%, highly_variable > 50%
- **Register:** Add API route in `routes/api.php` under metrics group
- **Test:** Create `tests/Feature/Metrics/IncomeStabilityMetricTest.php`

---

#### A10 — Budget Allocation Comparison Metric
- [ ] **Create `app/Domains/Metrics/Metrics/BudgetAllocationMetric.php`**
- **What:** Compare user's actual spending allocation against the 50/30/20 rule
- **Logic:**
  - Map categories to needs/wants/savings (use category type: EXPENSES → needs/wants based on category name heuristics, SAVINGS → savings, INVESTMENT → savings)
  - Calculate actual percentages of income
- **Return data:** `{ actual: { needs: float, wants: float, savings: float }, recommended: { needs: 50, wants: 30, savings: 20 }, income: float, differences: { needs: float, wants: float, savings: float } }`
- **Register:** Add API route in `routes/api.php` under metrics group
- **Test:** Create `tests/Feature/Metrics/BudgetAllocationMetricTest.php`

---

### Section B: Dashboard Widgets (Frontend)

#### B1 — Cash Flow Widget
- [ ] **Create React component at `resources/js/components/widgets/CashFlowWidget.tsx`**
- **What:** Display income, expenses, and net cash flow with period comparison
- **UI:** Card with income (green), expenses (red), net (green/red based on sign). Show % change from previous period
- **Data source:** Fetch from Cash Flow Metric API (A1)
- **Pattern:** Follow existing widget patterns in `Dashboard.tsx` — use deferred props or Inertia polling
- **Dark mode:** Must support dark mode with `dark:` classes
- **Integrate:** Add to `Dashboard.tsx` layout

---

#### B2 — Savings Rate Widget
- [ ] **Create React component at `resources/js/components/widgets/SavingsRateWidget.tsx`**
- **What:** Circular progress indicator showing savings rate percentage
- **UI:** Donut/ring chart showing rate vs target (20%). Color: green >= 20%, yellow 10-19%, red < 10%. Show gap amount below
- **Data source:** Savings Rate Metric API (A2)
- **Integrate:** Add to `Dashboard.tsx` layout

---

#### B3 — Emergency Fund Widget
- [ ] **Create React component at `resources/js/components/widgets/EmergencyFundWidget.tsx`**
- **What:** Show months of expenses covered with progress bar
- **UI:** Large number for months covered. Progress bar from 0 to 6 months with 3-month and 6-month markers. Color coded by status
- **Data source:** Emergency Fund Metric API (A3)
- **Integrate:** Add to `Dashboard.tsx` layout

---

#### B4 — Financial Health Score Widget
- [ ] **Create React component at `resources/js/components/widgets/FinancialHealthScoreWidget.tsx`**
- **What:** Display composite score (0-100) with breakdown
- **UI:** Large score number with status text. 4 sub-bars showing each component score. Color gradient based on total score
- **Data source:** Financial Health Score Metric API (A4)
- **Integrate:** Add to `Dashboard.tsx` layout — place prominently at top

---

#### B5 — Spending Alerts Widget
- [ ] **Create React component at `resources/js/components/widgets/SpendingAlertsWidget.tsx`**
- **What:** Show list of financial alerts sorted by severity
- **UI:** Alert cards with icon (warning triangle, check, X), color-coded border (red/yellow/green), title and message. Dismissable
- **Data source:** Spending Alerts Metric API (A5)
- **Integrate:** Add to `Dashboard.tsx` layout — sidebar or top section

---

#### B6 — Top Expenses Widget
- [ ] **Create React component at `resources/js/components/widgets/TopExpensesWidget.tsx`**
- **What:** Table of top 10 expenses with brand, amount, category, date
- **UI:** Compact table with rows. Amount right-aligned in MYR format. "View All" link to `/transactions`
- **Data source:** Top Expenses Metric API (A6)
- **Integrate:** Add to `Dashboard.tsx` layout

---

#### B7 — Recurring Expenses Widget
- [ ] **Create React component at `resources/js/components/widgets/RecurringExpensesWidget.tsx`**
- **What:** List detected recurring expenses with total monthly cost
- **UI:** List items: brand name, monthly amount, last date. Stale items highlighted with "Possibly cancelled?" badge. Footer: total monthly recurring cost
- **Data source:** Recurring Expenses Metric API (A7)
- **Integrate:** Add to `Dashboard.tsx` layout

---

#### B8 — Cash Runway Widget
- [ ] **Create React component at `resources/js/components/widgets/CashRunwayWidget.tsx`**
- **What:** Show months of runway remaining
- **UI:** Large number for months. Monthly burn rate below. Status color indicator
- **Data source:** Cash Runway Metric API (A8)
- **Integrate:** Add to `Dashboard.tsx` layout

---

#### B9 — Quick Actions Widget
- [ ] **Create React component at `resources/js/components/widgets/QuickActionsWidget.tsx`**
- **What:** Action buttons for common tasks
- **UI:** Row of icon buttons: Add Transaction (links to `/transactions` with create modal), Scan Receipt (`/transactions/scan-receipt`), Create Budget, Export Report (`/exports`)
- **Uses:** Inertia `<Link>` for navigation
- **Integrate:** Add to `Dashboard.tsx` layout — top of page or floating

---

### Section C: Tags System

#### C1 — Tags Migration & Model
- [ ] **Create migration and model for tags**
- **Migration:** `tags` table — `id`, `name` (string, unique per user), `color` (string, nullable, hex), `user_id` (foreign key), timestamps
- **Migration:** `taggables` pivot table — `tag_id`, `taggable_id`, `taggable_type` (polymorphic, initially for Transaction)
- **Model:** `app/Models/Tag.php` with `user()` belongsTo, `transactions()` morphedByMany
- **Add to Transaction model:** `tags()` morphToMany relationship
- **Factory:** Create `TagFactory` with states
- **Test:** `tests/Feature/Tags/TagModelTest.php` — test relationships, scoping by user

---

#### C2 — Tags CRUD API
- [ ] **Create tags API endpoints**
- **Controller:** `app/Http/Controllers/Api/V1/TagController.php` — index, store, update, destroy
- **Form Request:** `app/Http/Requests/TagRequest.php` — validate name (required, max 50, unique per user), color (nullable, hex format)
- **Routes:** Add to `routes/api.php` under v1 group: `GET /tags`, `POST /tags`, `PUT /tags/{tag}`, `DELETE /tags/{tag}`
- **Policy:** Users can only manage their own tags
- **Test:** `tests/Feature/Tags/TagApiTest.php`
  - Test CRUD operations
  - Test user scoping (can't access other user's tags)
  - Test validation (duplicate names, invalid color)
  - Test delete cascades to taggables pivot

---

#### C3 — Tag Transactions
- [ ] **Add tagging support to transaction endpoints**
- **Modify:** Transaction store/update API to accept `tag_ids: int[]` parameter
- **Modify:** Transaction API responses to include `tags` relationship (eager loaded)
- **Modify:** Transaction index to support `?tag=<id>` filter
- **Test:** `tests/Feature/Tags/TagTransactionTest.php`
  - Test attaching tags when creating transaction
  - Test updating tags on transaction
  - Test filtering transactions by tag
  - Test removing tags

---

#### C4 — Tags Management Page (Frontend)
- [ ] **Create `resources/js/pages/Settings/Tags.tsx`**
- **What:** CRUD page for managing tags under Settings > Transactions > Tags
- **UI:** List of tags with color dot, name, transaction count. Create/edit inline or modal. Delete with confirmation
- **Route:** Add web route `/settings/tags` rendering Inertia page
- **Controller:** `app/Http/Controllers/Settings/TagController.php` — index page
- **Update:** Settings sidebar to link to this page (currently placeholder `#` href)

---

### Section D: Import System

#### D1 — CSV/Excel Import Backend
- [ ] **Create import system for transactions**
- **Import class:** `app/Imports/TransactionsImport.php` using `maatwebsite/excel` (already installed for export)
- **Supported columns:** `date`, `amount`, `brand` (name, auto-create if missing), `category` (name, must exist), `note`, `type` (income/expense/savings/investment)
- **Controller:** `app/Http/Controllers/ImportController.php` — show upload form, process import
- **Form Request:** `app/Http/Requests/ImportTransactionsRequest.php` — validate file (csv, xlsx, max 5MB)
- **Route:** `POST /import/transactions` — process uploaded file
- **Queue:** Use `ShouldQueue` on the import for large files
- **Return:** Summary of imported/skipped/failed rows with error details
- **Test:** `tests/Feature/Import/TransactionImportTest.php`
  - Test successful CSV import
  - Test successful Excel import
  - Test invalid file rejected
  - Test missing required columns
  - Test auto-create brand
  - Test skip rows with invalid category
  - Test user_id automatically set
  - Test duplicate detection (same date + amount + brand)

---

#### D2 — Import Page (Frontend)
- [ ] **Create `resources/js/pages/Settings/Import.tsx`**
- **What:** File upload page for importing transactions
- **UI:**
  - Drag-and-drop file upload zone (CSV/XLSX)
  - Download sample template link
  - Column mapping preview (show first 5 rows)
  - Import button with progress
  - Results summary: imported count, skipped count, error list
- **Route:** Add web route `/settings/import` rendering Inertia page
- **Update:** Settings sidebar Import link (currently placeholder `#` href)

---

#### D3 — Import Template Download
- [ ] **Create downloadable import template**
- **Route:** `GET /import/template` — download a sample CSV with headers and 2 example rows
- **Headers:** `date,amount,brand,category,note,type`
- **Example rows:** Show valid data format
- **Controller:** Add `template()` method to `ImportController`
- **Test:** `tests/Feature/Import/ImportTemplateTest.php` — test download returns CSV with correct headers

---

### Section E: Settings Pages Completion

#### E1 — Preferences Page
- [ ] **Create `resources/js/pages/Settings/Preferences.tsx`**
- **What:** User preferences: locale, timezone, currency, date format
- **Data:** Read from `user_settings` table, save via existing settings API
- **Fields:**
  - Locale dropdown (en, ms)
  - Timezone dropdown (common timezones, default Asia/Kuala_Lumpur)
  - Currency display (MYR — read-only for now)
  - Date format (DD/MM/YYYY, MM/DD/YYYY, YYYY-MM-DD)
- **Route:** `GET /settings/preferences` — web route
- **Controller:** `app/Http/Controllers/Settings/PreferencesController.php`
- **Update:** Settings sidebar Preferences link
- **Test:** `tests/Feature/Settings/PreferencesTest.php`

---

#### E2 — API Key Management Page
- [ ] **Create API key management feature**
- **Migration:** `personal_access_tokens` — use Laravel Sanctum (already installed)
- **Page:** `resources/js/pages/Settings/ApiKeys.tsx`
- **UI:**
  - List of active API keys with name, created date, last used date
  - Create new key: name input + generate button → show token once
  - Revoke key: delete button with confirmation
- **Controller:** `app/Http/Controllers/Settings/ApiKeyController.php` — index, store, destroy
- **Route:** `GET /settings/api-keys`, `POST /settings/api-keys`, `DELETE /settings/api-keys/{token}`
- **Update:** Settings sidebar API Key link
- **Test:** `tests/Feature/Settings/ApiKeyTest.php`
  - Test create token returns plain text token
  - Test list tokens (without plain text)
  - Test revoke token
  - Test revoked token can't access API

---

#### E3 — SMS Parser Rules Page
- [ ] **Create `resources/js/pages/Settings/SmsParserRules.tsx`**
- **What:** UI to view and manage SMS parsing templates from `config/hisabi.php`
- **Logic:** Store custom rules in `user_settings` table as JSON, falling back to config defaults
- **UI:**
  - List of parsing rules: bank name, pattern, sample SMS
  - Test rule: paste SMS → show parsed output
  - Add/edit custom rules (regex pattern, field mapping)
- **Controller:** `app/Http/Controllers/Settings/SmsParserRulesController.php`
- **Route:** `GET /settings/sms-rules`
- **Test:** `tests/Feature/Settings/SmsParserRulesTest.php`

---

### Section F: Admin Enhancements

#### F1 — Audit Log System
- [ ] **Create audit log for admin visibility**
- **Migration:** `audit_logs` table — `id`, `user_id`, `action` (string), `auditable_type`, `auditable_id`, `old_values` (JSON), `new_values` (JSON), `ip_address`, `user_agent`, timestamps
- **Model:** `app/Models/AuditLog.php`
- **Trait:** `app/Traits/Auditable.php` — auto-log create/update/delete on models that use it
- **Apply trait to:** User, Transaction, Budget models
- **Admin page:** `resources/js/pages/Admin/AuditLog/Index.tsx` — searchable, filterable table
- **Controller:** `app/Http/Controllers/Admin/AuditLogController.php`
- **Route:** `GET /admin/audit-log` (admin middleware)
- **Test:** `tests/Feature/Admin/AuditLogTest.php`
  - Test log created on model create
  - Test log created on model update with old/new values
  - Test log created on model delete
  - Test admin can view logs
  - Test non-admin cannot view logs
  - Test filtering by user, action, model type

---

#### F2 — System Health Dashboard
- [ ] **Create admin system health page**
- **Page:** `resources/js/pages/Admin/Health/Index.tsx`
- **Checks:**
  - Database connectivity (query `SELECT 1`)
  - Redis connectivity (ping)
  - OCR service status (existing `/api/v1/ocr/status` endpoint)
  - Disk usage (storage directory)
  - Queue status (pending/failed jobs count)
  - Last backup date (if configured)
- **Controller:** `app/Http/Controllers/Admin/HealthController.php`
- **Route:** `GET /admin/health` (admin middleware)
- **Test:** `tests/Feature/Admin/HealthTest.php`
  - Test admin can view health page
  - Test non-admin gets forbidden
  - Test health check response format

---

### Section G: Financial Forecasting

#### G1 — Net Worth Projection Metric
- [ ] **Create `app/Domains/Metrics/Metrics/NetWorthProjectionMetric.php`**
- **What:** Project net worth 6 months forward based on average monthly net savings rate
- **Algorithm:**
  1. Calculate average monthly net change over last 6 months (income - expenses + savings + investments)
  2. Project forward: `current_net_worth + (avg_monthly_change * months_ahead)`
  3. Return projections for months 1-6
- **Return data:** `{ current_net_worth: float, avg_monthly_change: float, projections: [{ month: string, projected: float }] }`
- **Register:** Add API route
- **Test:** `tests/Feature/Metrics/NetWorthProjectionMetricTest.php`

---

#### G2 — Projection Chart Widget
- [ ] **Create React component at `resources/js/components/widgets/ProjectionWidget.tsx`**
- **What:** Line chart showing historical net worth + projected future (dashed line)
- **UI:** Existing net worth trend line extended with dashed projection line for 6 months. Different color/style for projected vs actual
- **Data source:** Combine `NetWorthTrendMetric` (historical) + `NetWorthProjectionMetric` (future)
- **Integrate:** Add to `Dashboard.tsx` or as tab on existing net worth chart

---

## Summary

| Section | Tasks | Description |
|---------|-------|-------------|
| A | A1-A10 | Backend metrics (10 new metric classes) |
| B | B1-B9 | Dashboard widgets (9 React components) |
| C | C1-C4 | Tags system (model, API, UI) |
| D | D1-D3 | Import system (CSV/Excel upload) |
| E | E1-E3 | Settings pages completion |
| F | F1-F2 | Admin enhancements |
| G | G1-G2 | Financial forecasting |

**Total: 33 tasks**

## Dependency Order

```
A1 → B1  (Cash Flow metric before widget)
A2 → B2  (Savings Rate metric before widget)
A3 → B3  (Emergency Fund metric before widget)
A4 → B4  (Health Score metric before widget)
A5 → B5  (Spending Alerts metric before widget)
A6 → B6  (Top Expenses metric before widget)
A7 → B7  (Recurring Expenses metric before widget)
A8 → B8  (Cash Runway metric before widget)
B9 has no backend dependency (links only)
C1 → C2 → C3 → C4  (Tags: model → API → transaction integration → UI)
D1 → D2  (Import backend before frontend)
D3 is independent
E1, E2, E3 are independent of each other
F1, F2 are independent of each other
G1 → G2  (Projection metric before widget)
```

## Recommended Execution Order

1. A1-A10 (all backend metrics — no frontend dependency)
2. C1-C2 (tags model + API — foundational)
3. D1, D3 (import backend + template)
4. B1-B9 (all dashboard widgets)
5. C3-C4 (tags transaction integration + UI)
6. D2 (import frontend)
7. E1-E3 (settings pages)
8. F1-F2 (admin features)
9. G1-G2 (forecasting)
