# FreshMart POS — TODO / Known Issues

Developer-facing backlog (the user-facing feature list is in `Features.md`).
Grouped by priority. Add new items as they come up.

---

## 🔴 To fix (functional)

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

### Purchase edit/delete doesn't reverse stock or cost layers
Deleting or editing a purchase does not roll back the `stock_layers` it created or the `stock`
aggregate. Add reversal (remove/where-possible the purchase's layers + decrement aggregate),
guarding against layers already partly sold.

---

## 🟡 Cleanups
- **Remove the unused `variation_type_id` selector** from product create/edit (variable products
  idea was dropped 2026-07-01; the selector is never persisted).
- **Product edit `description`** is a single-line `<input>`; make it a `<textarea>` like create.
- **`discount_percent`** is stored on products but unused in the POS — wire it in or drop it.

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
