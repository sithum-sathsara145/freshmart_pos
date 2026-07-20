# FreshMart POS — Features

A plain-English guide to what the FreshMart point-of-sale system can do.
Written for everyone (no technical knowledge needed).

> **Tip:** Some newer features are still being tested before they go live for everyone.
> A short list of the latest additions is at the bottom under **"Recently Added & Requested."**

---

## 🛒 Point of Sale (the checkout screen)

- **Fast checkout** built for a busy counter, with a full-screen mode.
- **Search products** by typing a name, with instant suggestions as you type.
- **Barcode scanning** — works with normal product barcodes, store-made barcodes, and weighing-scale barcodes.
- **Shopping cart** with quantity controls and a running total.
- **Discounts & tax** — add a discount as a rupee amount or a percentage; add tax per sale.
- **Cash & card payments** — confirmation popups before finishing; for card, the last 4 digits are recorded.
- **Weighed items** — products sold by weight (vegetables, meat, etc.) are priced automatically from the scale barcode.
- **Choose-a-price popup** — if the same product is in stock at more than one selling price, the cashier is asked which price to use.
- **Keyboard shortcuts (F1–F9)** and a **built-in calculator** for speed.
- **Counter cash sessions** — open and close the till with a cash count (denomination by denomination, including Rs. 2000 notes); the system checks the expected vs. counted amount. Sales are blocked until the counter is opened.

## 📦 Products

- **Add, edit and manage products** with category, brand, unit, and description.
- **Unique 6-digit item code (SKU)** — created automatically, editable.
- **Product photos** — upload an image with a built-in editor (crop, zoom, rotate, flip); the photo is automatically resized, compressed, and uploaded with a live progress bar.
- **Maximum Retail Price (MRP)** stored alongside cost and selling price.
- **Weighed products** — mark items that are sold by weight.
- **Online store visibility** — flag which products appear in the online store.
- **Bulk import** — add many products at once from an Excel or CSV file; existing products are automatically skipped so you can re-upload safely.
- **Export** — download your product list as Excel or CSV (respects your current filters).
- **Sample files** — download a ready-made example Excel/CSV to see the correct format.

## 🏷️ Barcodes & Labels

- **Automatic barcodes** for store-made items that don't come with one — generated so they never clash with real manufacturer barcodes.
- **Weighing-scale barcodes** — supports scale-printed barcodes that carry the item's weight or price (configurable to match common scales).
- **Print a single barcode** or **print labels in bulk** for many products at once.
- **Label designer** — a true-to-size print preview where you can set the paper size (A4 sheet or thermal roll), margins, number of columns, spacing, barcode height, font size, and choose what shows on each label (business name, product name, barcode number, price).

## 📊 Inventory & Stock

- **Live stock levels** per branch, with **low-stock** and **out-of-stock** alerts.
- **Stock adjustments** — add, remove, or correct stock, with reasons (damage, expiry, recount, etc.).
- **Stock transfers** between branches.
- **Batch numbers** — record a batch/lot number when receiving goods.
- **Smart cost tracking:**
  - For regular items, the **oldest stock is sold first** (FIFO), so costs and profit stay accurate.
  - For weighed items, an **average cost** is kept as new stock arrives.
- **Accurate profit** — the real cost of each sale is recorded, so profit reports reflect true margins.

## 🚚 Purchases (buying stock)

- **Purchase orders** with supplier, dates, and payment details.
- **Add items by name or barcode**, showing both for easy selection.
- **Per-item details** — batch number, purchase price, MRP, and selling price.
- **Automatic pricing logic when costs change:**
  - Weighed items: recalculates the average cost and lets you set a new selling price (with the new average shown).
  - Regular items at the same price: kept as separate cost batches and sold oldest-first.
  - Regular items at a new selling price: that price becomes a second option at the checkout (same barcode/SKU).
- **Supplier balances** update automatically, and payments are recorded.
- **Purchase bill** can be downloaded as a PDF.

## 💳 Sales, Returns & Customers

- **Sales records and invoices.**
- **Sale returns / credit notes** — returned items go back into stock automatically.
- **Loyalty points** for customers and **discount coupons.**
- **Customer and supplier** records with balances.
- **Online orders** view.

## 📈 Reports & Insights

- **Profit & Loss** — now based on the true cost of goods sold.
- **Top-selling products** with their profit.
- **Stock summary** and **stock alerts.**
- **Product sales** reports.
- **Dashboard** with key numbers at a glance.

## ⚙️ Settings & Administration

- **Business info, receipt setup, tax settings, and hardware** options.
- **API keys** — securely store keys for outside services (e.g. the image service); secrets are encrypted and never shown again.
- **Scale barcode settings** — configure the format your weighing scale prints.
- **Internal barcode prefix** — control the range used for store-made barcodes.
- **Branches, counters, and users & roles.**
- **Expenses, accounts, and payments.**

---

## 🆕 Recently Added & Requested

_Newest first. New ideas and improvements are added here as they come up._

<!-- NEW-FEATURES -->
- **2026-07-20 — End-of-day cash float & banking** — Set a float per counter that stays in the drawer overnight. When the counter is closed, the rest is banked into the cash or bank account the cashier picks.
- **2026-07-20 — Import received goods** — Upload a supplier's invoice as a spreadsheet to add stock for many products at once. It creates a real purchase at the cost you were charged and puts the amount on the supplier's balance.
- **2026-07-20 — Supplier list import & export** — Download the supplier list as CSV/Excel, edit it, and upload it back — matching rows update instead of duplicating.
- **2026-07-19 — Light & dark mode** — The whole system now comes in a light theme as well as the original dark one. Use the sun/moon button in the top bar to switch; your choice is remembered on that device.
- **2026-07-19 — CSV import updates existing products** — Re-importing a product (matched by SKU/barcode/name) now updates its prices and stock in place instead of skipping or duplicating it.
- **2026-07-19 — Product bulk delete** — Select multiple products on the Products page and delete them at once (products with sales history are skipped).
- **2026-07-19 — Credit sales** — Sell to approved customers on credit — cash/card/credit split tender, a signed credit bill showing NIC/address and a signature line, and a photo of the signed copy uploaded by webcam or the cashier's phone via QR (code- or password-gated).
- **2026-07-19 — Staff reports** — Three new reports: attendance summary (days, hours, overtime per person), the monthly payroll register or salary sheet, and a leave summary showing entitled, used and remaining days. All three export to PDF, Excel and CSV.
- **2026-07-19 — My HR for staff** — Every employee can sign in and see their own attendance, hours, leave balance and payslips, and request leave themselves — without being able to see anyone else's records.
- **2026-07-19 — Leave balances** — Each staff member has annual, casual and sick day allowances per year. The system shows how many days are left, refuses requests that would go over, and does not charge leave for public holidays.
- **2026-07-19 — Attendance from the till** — When a cashier opens their counter session they are marked present automatically, and closing it records their hours and overtime. Managers get a daily sheet listing every staff member, and staff can check themselves in and out.
- **2026-07-19 — Correct salaries with payslips** — Payroll now calculates Sri Lankan EPF and ETF the right way round — ETF is the shop's contribution and is no longer taken out of anyone's pay. Allowances and deductions are included, re-generating a month no longer wipes hand-entered figures or un-pays anyone, and every employee gets a printable payslip.
- **2026-07-19 — Report downloads (PDF / Excel / CSV)** — Every report page's export buttons now really work — sales, expenses, payments, product sales, profit & loss, rate list and stock alerts can be downloaded as a PDF, Excel or CSV file for the selected date range and branch.
- **2026-07-16 — Branch switcher for admins** — Admins and the developer account can switch which branch they are working in, or pick All branches to see every branch's figures at once. All-branches is view-only: you must pick a branch before creating a sale, purchase or expense. Other staff stay locked to their own branch.
- **2026-07-16 — User roles & permissions** — Five ranked roles (Admin, Manager, Stock Manager, Cashier, plus a hidden developer account). Admins can create roles, tick exactly which screens each role may open, and add staff — but only at or below their own rank, so nobody can promote themselves.
- **2026-07-09 — System cleanup: expenses, accounts, HRM & more** — Deleting or editing an expense now correctly puts the money back into (or adjusts) the account it was paid from. Suppliers, customers and accounts with history can no longer be deleted into a broken state. The missing screens were built: edit supplier, add account, edit expense, add/edit/view staff, new leave request, and edit attendance. Online orders can be deleted cleanly and converting one to a sale no longer marks it delivered if the sale fails.
- **2026-07-07 — Purchase order: add items, custom lines, instant pay** — When creating a purchase order you can now: create a brand-new product on the spot (a quick form right in the search box) and drop it straight onto the order; add custom/temporary lines (name + qty + price) for one-off buys that shouldn't be tracked as stock; and pay the supplier immediately by entering an amount and choosing which account it comes from, which records the payment out and updates the account balance.
- **2026-07-07 — Purchase returns rebuilt** — Purchase returns (debit notes) now work properly: you pick the actual items from the bill with quantities capped at what's still in stock, and sending goods back to the supplier correctly reduces stock. The credit is settled the way you choose — reduce what you owe the supplier, take a cash refund into the till, or mark it for replacement — and a return can be reversed. Staff also only see purchases from their own branch.
- **2026-07-07 — Quotation + payment fixes** — The New Quotation screen now has a proper product search (you pick real products instead of typing a name that failed to save). Recording a customer or supplier payment can no longer overpay an invoice — the amount is capped to what's actually owed — and the Payment In summary now shows this branch's totals only. Quotations can also be deleted from the list.
- **2026-07-07 — Back-office sale form fixed** — The back-office New Sale screen now works end to end: it lists products and accounts, supports Cash/Card/Bank/Credit payments, applies per-line discounts, saves the remark, and records the payment against the chosen account. Deleting a quotation now works cleanly, and staff can only open, print or edit records belonging to their own branch.
- **2026-07-07 — Void sale + quotation to sale** — Completed sales can now be voided from the invoice screen, which correctly puts the stock back, refunds the payment and reverses loyalty points. Quotations can be turned into a sale: 'Convert to sale' now opens a New Sale screen pre-filled with the quote's customer and items for review, and the quotation is only marked converted once that sale is saved.
- **2026-07-06 — Sales returns rebuilt** — Sales returns now pick real items from the chosen invoice with per-line return quantities capped at what was actually sold (minus earlier returns); partial and repeat returns are supported without voiding the rest of the invoice; cash refunds now correctly leave the till; and profit/sales reports subtract returns so the numbers stay accurate.
- **2026-07-06 — Edit & delete purchases** — You can now edit or delete a purchase order after saving it, as long as none of its stock has been sold yet — the system automatically reverses the stock and cost record. If some of it has already been sold, editing/deleting is blocked and you're pointed to Purchase Return instead.
- **2026-07-01 — Split payment (cash + card)** — Pay one bill partly by card and partly by cash; only the cash portion is counted in the till reconciliation.
- **2026-07-01 — Hold & resume bills** — Park an in-progress sale (with an optional label) to serve another customer, then resume it later from the Held list.
- **2026-06-30 — Oversell prevention** — Checkout blocks adding or selling more than the available stock of an item.
- **2026-06-30 — Custom items at checkout** — Add a one-off item (name, price, quantity) to a sale that isn't in the catalogue; it doesn't affect stock.
- **2026-06-30 — Editable price at checkout** — Cashiers can tap a cart line's unit price to override it for that sale; stock and cost stay correct.
- **2026-06-30 — POS stock display** — The checkout grid hides out-of-stock items and refreshes stock counts right after each sale.
- **2026-06-30 — Features document** — Added a plain-English Features.md and a script to keep it updated.
- **2026-06-30 — Inventory costing (batches, FIFO & average cost)** — purchases now track batch numbers, MRP, and per-batch cost/price; sales record true cost so profit is accurate.
- **2026-06-30 — Choose-a-price at checkout** — when a product has more than one selling price in stock, the cashier picks which one.
- **2026-06-30 — Stock screen fixes** — proper product selector on adjustments and transfers.
- **2026-06-30 — Bulk product import & export** — add/download products via Excel or CSV, with sample files; duplicates are skipped.
- **2026-06-30 — Bulk barcode labels with a true-size print designer.**
- **2026-06-30 — Weighing-scale & store-made barcodes.**
- **2026-06-30 — Secure API keys settings tab.**
- **2026-06-30 — Product image editor with direct, progress-tracked upload.**

---

### How this document is maintained

This file is the **non-technical feature list**. Whenever a new feature or improvement is
suggested and acted on, a one-line entry is added to **"Recently Added & Requested"** above
(newest first).

To add an entry yourself, run:

```bash
php scripts/add-feature.php "Short title" "One-line plain-English description"
```

The new line is inserted automatically at the top of the list with today's date.
