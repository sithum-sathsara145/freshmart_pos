# FreshMart POS — TODO / Known Issues

Developer-facing backlog (the user-facing feature list is in `Features.md`).
Grouped by priority. Add new items as they come up.

---

## 📊 Reports overhaul — FULL PLAN  _(planned 2026-07-09, not started)_

**Goal:** a Google-Analytics-style reporting suite — simple, visual, glanceable. Every report =
KPI scorecards with change-vs-previous-period, one big trend chart, a couple of breakdown
panels, and one clean detail table. Covers sales, revenue/profit, purchases, stock movement,
counter sessions, and payments/cash flow.

### Current state (what we build on)
- 9 table-only report views in `resources/views/reports/` driven by `ReportController`
  (profit_loss, sales_summary, stock_summary, stock_alert, rate_list, product_sales,
  payments, expenses, user_reports). No charting library anywhere — the dashboard "chart"
  is hand-rolled divs. Date filter = two bare date inputs, no presets, no comparison.
- No report exists for **counter sessions** (table already stores expected_closing /
  closing_balance / variance / opened_by / closed_by) or **stock movement** (data exists in
  stock_layers + stock_adjustments + stock_transfers + purchase/sale/return items).
- Sales/P&L/product-sales/dashboard are already **returns-netted** via
  `ReportController::returnTotals()` / `returnsByProduct()` — reuse these.
- Export pipeline REBUILT (2026-07-19): `reports.export/{type}` now serves PDF (DomPDF,
  shared `reports/export/table_pdf` view) + Excel/CSV (OpenSpout — Maatwebsite was never
  installed) for sales / expenses / payments / product_sales / profit_loss / rate_list /
  stock_alert, branch-scoped and returns-netted. Extend `exportSpec()` for new reports.

### Design system (GA look, adapted to the dark UI)
1. **Shared report shell** (`reports/layout` + partials) used by every report:
   - Header: report title + **date-range picker with presets** (Today, Yesterday, Last 7 days,
     Last 30 days, This month, Last month, This year, Custom from/to) + a **"Compare: previous
     period" toggle**. Selection persists across reports via query string.
   - **KPI scorecard row**: big number, label, and delta vs previous period (▲ green / ▼ red,
     with % change) — deltas only when compare is on. 3–6 cards per report.
   - **Primary trend chart**: line/area of the report's main metric bucketed by day (hour
     buckets when range = 1 day, month buckets when range > 62 days). Dashed second series =
     previous period when compare is on.
   - **Breakdown row**: 2 side-by-side panels (donut or horizontal bar) e.g. "by payment
     method", "by category".
   - **Detail table**: sortable, max ~25 rows + link to the full module page; Export
     PDF/Excel buttons top-right.
2. **Charts: ApexCharts, vendored locally** (`public/vendor/apexcharts.min.js` — no CDN
   dependency, no build step, dark-mode friendly, tooltips/legends out of the box). Charts are
   client-side only, so the no-GD constraint doesn't matter; PDF exports stay table-based.
3. **Shared server helper `App\Support\ReportRange`**: resolves preset → [from, to], computes
   the equal-length previous period, picks the bucket granularity (hour/day/month), and emits
   the date-series (zero-filled buckets so charts don't skip empty days). All report queries
   stay branch-scoped and returns-netted.
4. **Reports landing page** (`/reports`) — a GA-style hub: one card per report with a live
   mini-KPI + sparkline; sidebar gets a Reports submenu instead of the single profit-loss link.
5. Color language everywhere: green = money in / growth, red = money out / decline,
   indigo = neutral counts. `Rs.` formatting via one shared Blade helper.

### The reports
- **A. Sales** _(rework `sales_summary`)_ — KPIs: Net sales, Gross, Returns, Invoices,
  Avg basket value, Items per invoice. Trend: net sales/day (+compare). Breakdowns: payment
  method donut; sales by hour-of-day bar ("peak hours"); by cashier; by category. Table:
  day rows (date, invoices, gross, returns, net, avg basket).
- **B. Revenue & Profit** _(rework `profit_loss`)_ — KPIs: Net revenue, COGS, Gross profit,
  Margin %, Expenses, Net profit. Trend: revenue vs COGS vs profit (stacked/multi-line).
  Breakdowns: expenses by category donut; top-10 products by profit bar. Table: monthly or
  daily P&L rows. Note on-screen: pre-2026-07 COGS is approximate (historical backfill).
- **C. Purchases** _(new)_ — KPIs: Total purchased, Bills, Paid, Outstanding payables,
  Purchase returns. Trend: purchase value/day. Breakdowns: top suppliers bar; payment-status
  split (paid/partial/unpaid). Table: largest bills (bill no, supplier, total, paid, due).
- **D. Stock movement** _(new)_ — unified movement ledger from: purchases (+), purchase
  returns (−), sales (−), sale returns (+), adjustments (±, incl. damage/expired), transfers
  in/out. KPIs: Units in, Units out, Net change, Current stock value (Σ layers qty×cost),
  Write-offs (damage+expired qty & value). Trend: units in vs out per day. Breakdowns:
  movement by type donut; top movers bar. Table: per-product opening → in → out → closing
  for the period (derive closing from current stock walked back, or sum movements). Include
  the adjustments log with reasons.
- **E. Counter sessions** _(new)_ — KPIs: Sessions, Cash counted, Total variance,
  Sessions with variance, Avg session length. Trend: daily variance (bars ±). Breakdowns:
  variance by counter; variance by cashier (who's short most often). Table: session rows
  (counter, opened/closed by, opened/closed at, opening float, cash sales, expected,
  counted, variance chip green/red). Flag sessions open > 24 h. Links to existing
  counter-sessions show page.
- **F. Payments & cash flow** _(rework `payments`)_ — KPIs: Money in, Money out, Net cash
  flow, plus a card per account with current balance. Trend: in vs out per day. Breakdowns:
  by method donut; in/out by party type. Table: latest payments (keep existing, reskin).
- **Keep as-is (light reskin into the shell later):** stock_summary, stock_alert, rate_list,
  expenses, product_sales, user_reports.

### Phasing (each phase ships usable + verified)
1. **Phase 1 — Shell & plumbing: ✅ DONE (2026-07-09).** `App\Support\ReportRange` (presets,
   previous-period, hour/day/month buckets, zero-fill, `delta()`, `query()`); a **self-written**
   SVG charts helper `public/js/reportcharts.js` (line+compare/bar/hbar/donut, hover tooltips,
   dark theme — chose custom over ApexCharts: zero dependency, browser-verified); shared partials
   `reports/partials/{header,kpi,chart}.blade.php` (header = preset picker + custom dates + compare
   toggle, persists other query params); reports landing hub `ReportController@index` + `GET /reports`
   (`reports.index`) with a KPI row + net-sales trend + report-card grid (new reports flagged
   "soon"); sidebar "Reports" now points at the hub. Verified: ReportRange presets/prev/buckets via
   tinker; hub renders across presets + compare (hour/day/month, series index-aligned); all 4 chart
   types render in a real browser (php artisan serve + preview). `.claude/launch.json` added
   (`laravel` = artisan serve :8123) for browser checks in later phases.
2. **Phase 2 — Sales + Revenue & Profit** (highest value; replaces sales_summary +
   profit_loss internals, keeps same routes).
3. **Phase 3 — Purchases + Stock movement** (new controller methods + routes + views).
4. **Phase 4 — Counter sessions + Payments/cash flow.**
5. **Phase 5 — Polish:** reskin remaining table reports into the shell, add Excel/PDF export
   types for the new reports, landing-page sparklines, remove dead export views if unused.

### Constraints / notes
- MySQL 5.7 — no window functions/CTEs in grouped queries; zero-fill date buckets in PHP
  (`ReportRange`), not SQL.
- All queries branch-scoped; reuse returns-netting helpers so numbers match existing reports.
- Charts client-side only (no GD/Imagick on this box) — PDF exports remain tables.
- Verify each phase like the rest of the project: tinker-render every view, cross-check the
  KPI numbers against the current reports for the same range before swapping anything out.

---

## 🔴 To fix (functional)

### System-wide sweep — RESOLVED  _(2026-07-09)_
A full-system scan (model↔schema, controller↔view, FK-delete, money-consistency) found and fixed:
- **Expenses desynced accounts** — destroy() now restores the paying account's balance, update()
  adjusts it by the amount delta; both branch-scoped. Built `expenses/edit` (was a linked 500).
- **Guarded deletes** — suppliers (purchases/returns), accounts (payments/expenses or non-zero
  balance), customers (extended to online orders + sale returns), online orders (cascade items).
- **`OnlineOrderItem` missing `$timestamps=false`** → every insert 500'd. Fixed.
- **Online-order convert-to-sale** no longer marks the order delivered when the sale failed.
- **Missing linked views built**: `suppliers/edit`, `accounts/create`, `expenses/edit`,
  `hrm/staff/create+edit+show`, `hrm/leaves/create`, `hrm/attendance/edit`.
- **Dead-route cleanup**: removed customers/suppliers `ledger`, accounts `show/edit/update`,
  expenses `show`, online-orders `create/edit/store/update`, HRM stub actions with no views
  (attendance create/show, leaves show/edit/update, payroll show/create/store/edit, holidays
  create/show/edit/update), and the never-built `appreciations` module (controller + routes removed;
  table kept). Verified no view references any removed route.

### Sales / Quotations / Payments audit — ALL RESOLVED  _(2026-07-06 audit → fixed 2026-07-07)_
An audit of sales, quotations, payments-in and sales-returns found a batch of bugs; all are now fixed:
- **Sales-returns overhaul** — real item picker, qty caps, partial/repeat returns, cash refund leaves
  the till, reverse-return, reports net out returns.
- **`SaleController::destroy()`** — fully reverses (stock re-added, payments refunded to account,
  loyalty + customer totals restored, branch-scoped, blocks sales with returns) + "Void sale" button.
- **Quotation convert-to-sale** — opens a prefilled New Sale form for review; quote marked `converted`
  only on save. Required repairing the New Sale form (create() never passed `$products`/`$accounts`
  → 500; item rows sat outside the `<form>`), fixing `QuotationItem` (missing `$timestamps=false` →
  every quotation insert 500'd), and a latent `@json(...)`-with-commas Blade truncation bug.
- **Back-office payment methods** — added `bank_transfer` to `sales.payment_method` ENUM (live + both
  dumps); `store()` validates `in:cash,card,bank_transfer,credit`, honours the chosen `account_id`,
  and maps the sale method → a valid `payments.method` (`cash`/`card`/`bank`); reads the `note` field;
  applies per-line `discount_pct` into the subtotal + `sale_items.discount_percent`.
- **`SaleController::edit()`/`update()`** — removed (route `->except(['edit','update'])`). edit() 500'd
  on a missing view and update() bypassed the payment flow. Corrections go via Void/Return; payments
  via the Collect (payments-in) flow.
- **`QuotationController::destroy()`** — deletes `quotation_items` in a transaction first (was FK 500);
  branch-scoped.
- **Branch scoping** — added to sales `show`/`invoice`/`receipt` and quotations `show`/`edit`/`update`/
  `pdf`/`destroy`/`convert` (were resolvable by id across branches).
- **Returns netting** — completed: sales-summary `byCategory` + `byPaymentMethod` and the sales index
  header stats now subtract returns too (on top of the earlier P&L / product-sales / dashboard netting).

**Third audit pass (2026-07-07) — purchases / payments-out / purchase-returns:**
- **Purchase returns (debit notes) were a stub** — recorded an amount-only note with no stock
  effect, ignored the item rows + `credit_method`, used free-text `product_id`, `count()+1`
  dr-note numbering (collided with the UNIQUE column after any delete), no branch scope, blindly
  decremented supplier `balance_due` (could go negative), and `show()`/`edit()` 500'd on missing
  views with no-op `update()`/`destroy()`. **Full rebuild** (mirrors sale-returns): new
  `purchase_return_items` table + `purchase_returns.credit_method` (live DB + both dumps); real
  per-line item picker capped at each line's on-hand cost-layer qty; store() decrements the layer +
  aggregate (stock actually leaves), records reversed COGS, computes the amount, and settles by
  method — `credit_note` reduces payable (capped ≥0), `cash_refund` records a `payment_in` +
  account increment, `replacement` moves stock only; robust `DR-` numbering; branch-scoped; real
  show view + a working reverse (destroy) that restores stock and undoes the credit; removed
  edit/update (route `->except`).
- **Purchase branch scoping** — `show`/`edit`/`bill`/`update`/`destroy` now abort_if/scope by branch.
- **Payments-out `storeOut`** — was already hardened in the second pass (cap to balance, branch scope,
  guard); `indexOut` list is branch-scoped and has no totals card, so nothing else needed.

**Second audit pass (2026-07-07) — also fixed:**
- **Quotation create used a free-text `product_id`** (placeholder "Product name") → `exists:products,id`
  always failed, so quotations couldn't be created. Now a real searchable product picker (controller
  passes `$products`; `json_encode`, not `@json`); `store()` drops blank rows before validating.
- **Payments-in `storeIn`** — credited the account by the raw amount while the sale's `paid_amount`
  capped at total (overpayment inflated the balance). Now caps the amount to the balance due, scopes
  `sale_id`+`account_id` to the branch, guards an already-paid invoice, sets `party_id`, whitelists
  `method`. Same fix mirrored to `storeOut` (purchase payments).
- **Payments-in `indexIn`** — the cash/card summary cards summed **every branch**; now branch-scoped.
- **`quotations.edit`/`update`** — removed (route `->except`); `edit()` 500'd on a missing view and
  nothing linked to it. Added a **delete button** to the quotations index (`destroy()` works now).

_Note: the New Sale form's coupon "Apply" is still cosmetic (store() reads `coupon_id`, form sends
`coupon_code`) — not part of this audit; wire or drop it separately if desired._

### Counter session — orphaned "open" sessions  _(deferred — decide approach later)_
**Not a data-loss bug.** The session lives server-side in `counter_sessions`, and the POS
auto-resumes the open session on every load (`PosController@index`), so closing the browser
loses nothing and the running cash total is preserved. Auto-close / auto-timeout are
intentionally avoided because closing must capture the physical cash count, and a shift can
run long.

**The real gap:** `closeCounter()` only lets the cashier assigned to that counter
(`auth()->user()->counter`) close it. If that cashier leaves / the machine dies / the shift
ends, **nobody else can reconcile the session.**

**Recommended fix (pick when we return to it):**
1. **Manager/admin force-close** — close *any* open session from the Counter Sessions screen
   (enter counted cash + denominations; record who closed it + variance). This is the core fix.
2. **"Open too long" reminder** — non-blocking POS banner when the resumed session was opened
   before today / over N hours ago. Nudge, don't auto-close.
3. **Shift-handover info** — on resume, show who opened it and when, so a new cashier knows to
   close it and open their own.
- **Decision needed:** who may force-close — admin/manager role only, or any logged-in user.
- **Touch points:** `PosController@closeCounter`, `CounterSessionController` (index/show exist),
  `resources/views/counter-sessions/*`, `resources/views/pos/index.blade.php`.

---

## 🟡 Cleanups
- **Remove the unused `variation_type_id` selector** from product create/edit (variable products
  idea was dropped 2026-07-01; the selector is never persisted).
- **Product edit `description`** is a single-line `<input>`; make it a `<textarea>` like create.
- **`discount_percent`** is stored on products but unused in the POS — wire it in or drop it.
- **Known limitation (purchase edit, weighed items):** `PurchaseController::update()` locks
  qty/cost for existing weighed-product lines (server + client enforced) because their cost
  already fed the product's running weighted-average — un-blending it exactly would need
  replaying every purchase since. Only batch/MRP/sale price are editable on those lines; remove
  + re-add the line if the qty/cost genuinely needs to change.

---

## 🔒 Security / hardening
- **API keys settings tab is not role-gated** — restrict to admin/manager.
- **Cloudinary signature endpoint** (`GET /products/upload-signature`) is unthrottled — add rate limiting.
- **Orphaned Cloudinary images** when an upload is confirmed but the product is abandoned/replaced/
  removed — add a scheduled purge of unreferenced assets (not a live delete endpoint).

---

## 🧪 Testing — find the optimal flow (do test runs)
- **POS end-to-end:** multi-price choose-at-scan, weighed/scale items, custom items, price
  override, oversell guard, hold/resume bills — confirm each behaves as expected on real runs.
- **Inventory costing full cycle:** purchase (WAC / FIFO / multi-price) → sale (COGS) →
  sale return → stock adjustment → transfer. Confirm the invariant `stock aggregate == SUM(layer
  qty_remaining)` holds, and that the profit report matches.
- **Note:** the historical COGS backfill (72 sale lines) used *current* product cost as an
  approximation, so profit on pre-change sales is approximate.

---

## 🧹 Housekeeping
- Prune the merged `feature/product-images-cloudinary` branch (local + origin) — merged via PR #2.
