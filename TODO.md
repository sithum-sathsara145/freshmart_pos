# FreshMart POS — TODO / Known Issues

Developer-facing backlog (the user-facing feature list is in `Features.md`).
Grouped by priority. Add new items as they come up.

---

## 🔴 To fix (functional)

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
