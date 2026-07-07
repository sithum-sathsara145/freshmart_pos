# FreshMart POS — Project Handoff

Snapshot for picking the project up in a fresh session. See also `Features.md`
(non-technical feature list) and `TODO.md` (dev backlog).

---

## What it is
Laravel 12 + Alpine.js supermarket POS, Sri Lankan (LKR "Rs.").
Repo: `sithum-sathsara145/freshmart_pos`. Originally AI-generated, so it has
recurring structural bugs — verify things rather than trusting them.

## Environment (important, non-obvious)
- **Windows + MAMP**; **MySQL 5.7** @ `127.0.0.1:3306`, DB `freshmart_pos`; **PHP 8.2, NO GD/Imagick**.
- **Schema lives in SQL dumps, not migrations**: `freshmart_complete.sql` (schema+data),
  `database_schema.sql` (schema). Apply DB changes to the live DB via
  `php artisan tinker --execute "..."` **AND mirror into both dumps**.
- `composer require` needs **`--no-scripts`** (the package:discover hook hangs).
- **Verify by:** `php -l` (PHP), `php artisan view:cache` + `view:clear` (Blade),
  `node --check` on the extracted `<script>` (JS), `tinker` with
  `DB::beginTransaction()/rollBack()` (DB, non-destructive).
- Cloudinary cloud `da3s174op` (creds in `.env`, DB-first via API keys tab).
- Commits end with `Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>`.
  **Exclude `package-lock.json`; never commit `.env`.**

## Git state
- **Everything is on `main`** (origin + local), latest at handoff `e431e67`.
- All feature branches merged and **pruned** — only `main` remains.
- Currently committing **directly to `main`** (user's choice).

## Recurring bug patterns (check first)
- Singular DB tables vs plural models (`protected $table`).
- Some tables have **no timestamps** (e.g. `purchase_items` → `public $timestamps=false`).
- Controllers using unimported classes; garbled route strings.
- Free-text inputs where a numeric ID is required (was true on stock & purchase forms).
- Unchecked checkboxes don't persist — use `$request->boolean()`.

---

## Features built (recent major session)

- **Product images** — Cropper.js editor (crop/zoom/rotate/flip) → client resize + WebP →
  **direct upload to Cloudinary** via a signed endpoint (`GET /products/upload-signature`,
  secret stays server-side); AJAX form + inline errors; remove-image; server-side fallback.
- **API keys vault** — Settings "API keys" tab, config-driven (`config/api_credentials.php`);
  **secrets encrypted at rest** (`Crypt`/APP_KEY) via `Setting::getSecret/putSecret`;
  `CloudinaryService` reads creds **DB-first, then `.env`**.
- **Scale / weighed barcodes** — `products.is_weighed` + `scale_plu`; `App\Support\ScaleBarcode`
  parses GS1 prefix-`2` embedded EAN-13 (configurable "Scale barcodes" settings tab, off by
  default); POS scan resolves by PLU → weight/price.
- **Internal barcodes** — `ProductController::generateBarcode()` makes a valid EAN-13 in the GS1
  in-store range (prefix `21`); `findByBarcode` resolves **exact-match first, then scale-parse**.
- **Bulk barcode labels** — product-picker page + a **true-size print designer** (paper size,
  margins, columns, gaps, barcode height, font, content toggles).
- **Import / Export** — CSV/Excel product import via **OpenSpout** (no GD), ignores existing
  (SKU → barcode → name dedup), per-row atomic; export honoring filters + downloadable samples.
- **Inventory costing engine** — new **`stock_layers`** table (each purchase line = a layer with
  cost + sale price) + central **`App\Support\Inventory`** (`addLayer` / `consume`). Drives:
  - **WAC** for weighed items (manual sale price),
  - **FIFO** for non-weighed (oldest cost first),
  - **multi-price** (distinct in-stock sale prices become POS options),
  - batch numbers, MRP, and **true COGS** (`sale_items.cost`) → COGS-based profit report.
  Every stock writer (purchase, POS sale, `SaleController`, opening stock, adjustments,
  transfers, sale returns) routes through `Inventory`; invariant
  **`stock` aggregate == Σ `stock_layers.qty_remaining`** holds.
- **Stock form fixes** — real product selectors (were free-text), `createdBy` relations,
  branch name (was city), status validation, capped-removal logging.
- **POS checkout** — multi-price **choose-at-scan** popup; **hide out-of-stock** + refresh
  stock after each sale; **editable unit price** per line (with a layer-consume fallback so
  overrides don't desync stock/cost); **custom/one-off items** (`sale_items.product_id`
  nullable + `name`); **oversell prevention** (client + server, summed per product across
  price lines); **hold/resume bills** (`held_bills` table; park → resume → discard;
  server-side); **split payment cash + card** (`sales.cash_amount` so the till counts only the
  cash portion; two payment records; keyboard-navigable popup).
- **Docs** — `Features.md` + `scripts/add-feature.php` (auto-append dated entries); `TODO.md`.

### Earlier baseline (before the above)
Login fix; dashboard/table-name fixes; classmap autoloading for bundled controllers; missing
Brand/Category/Variation/ExpenseCategory/Banner CRUD; big POS overhaul (Alpine `posScreen()`);
6-digit editable SKU; counter cash sessions (open/close with denomination reconciliation).

## Key schema added (recent session)
- `products`: `mrp`, `is_weighed`, `scale_plu` (plus earlier `image_public_id`)
- `purchase_items`: `batch_no`, `mrp`, `sale_price`
- `sale_items`: `cost`, `name`, `product_id` made **nullable**
- `sales`: `cash_amount`
- **new tables**: `stock_layers`, `held_bills`
- `settings` table reused for encrypted API keys + scale/internal-barcode config

## Architecture notes
- POS is one Alpine component `posScreen()` in `resources/views/pos/index.blade.php`;
  modals use `x-teleport="body"`; server data via a `window.__POS` block.
- Product/POS search: `GET /api/products/search` (`ProductController@apiSearch`) — returns
  `price_options`, `is_weighed`, `mrp`, stock, etc.
- Barcode scan: `GET /pos/products/barcode/{barcode}` (`PosController@findByBarcode`).
- Sale: `POST /pos/sale` (`PosController@storeSale`) — consumes layers, records COGS,
  handles cash/card/split, oversell guard.
- Costing helpers: `App\Support\Inventory`, `App\Support\ScaleBarcode`.
- Barcode print uses Picqer `TYPE_CODE_128`.
- Some controllers are bundled in `app/Http/Controllers/RemainingControllers.php`
  (SettingController, BarcodeController, PurchaseReturnController, etc.) via classmap.

---

## Pending / TODO (details in `TODO.md`)
1. **Counter-session orphaned "open" sessions** _(deferred — too complex for now)._
   Not a bug: sessions are server-side and auto-resume on reload. Real gap: only the assigned
   cashier can close one → recommended fix is **manager/admin force-close** from the Counter
   Sessions screen (+ optional long-open reminder & shift-handover info). Decision pending:
   who may force-close (admin/manager role vs any user).
2. **Purchase edit/delete doesn't reverse stock/layers.**
3. **Cleanups**: remove the dead `variation_type_id` selector (variable products **dropped**);
   product-edit `description` → textarea; wire or drop `discount_percent`.
4. **Hardening**: role-gate the API-keys tab; throttle `/products/upload-signature`; scheduled
   purge of orphaned Cloudinary images.
5. **Do real test runs** — POS flows end-to-end + full inventory-costing cycle (confirm
   `aggregate == Σ layers` and the profit report). Note: historical COGS was backfilled with an
   approximation (current cost), so profit on pre-change sales is approximate.
