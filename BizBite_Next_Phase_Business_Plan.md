# BizBite — Focused Next Phase Plan

> Last verified against the codebase: **14 Sep 2026** — `php artisan test` → **43 passed (145 assertions)**.

## 0. Current project stage (live update)

The core POS and billing system is **built and working**. This is no longer a
plan on paper — Phase 1 is essentially complete.

**What is already built and running:**

- Laravel 11 backend (Livewire 3, Sanctum 4) with native PHP enums and typed models
- Multi-store (tenant) architecture — every table is store-scoped via `StoreScope`
- Admin portal (Laravel Livewire):
  - role login (admin / cashier split)
  - menu manager (categories, food items, prices, veg/non-veg/egg, GST slabs)
  - sales summary (today's revenue, bill count, payment-mode mix, recent bills)
  - receipt customizer (print header/footer for bills)
  - audit log viewer (owner audit trail)
- POS billing dashboard (Laravel Livewire, keyboard-first):
  - live search + category filter menu grid
  - instant add / quantity steppers / cart editing
  - live totals (BCMath, GST computed per item slab, discount, round-off)
  - payment selection — cash and UPI buttons in the web POS (the server + API accept all five modes: cash, upi, card, credit, split)
  - checkout via shared `OrderService`, native thermal print dialog, receipt data
  - shortcuts: F2 clear cart, F4 focus search, F8 cash checkout, F9 UPI checkout
- Order engine (`OrderService`, used identically by web POS, Sanctum API and
  the mobile app):
  - subtotal / discount / GST-per-item-slab / round-off / total (BCMath)
  - invoice number + per-store-per-day order number, idempotency key, UPI reference
  - order types (dine_in, takeaway, parcel, delivery) and statuses
    (pending → preparing → ready → completed, plus cancelled)
  - payment modes enum (cash, upi, card, credit, split) with per-order
    `payments` ledger row
  - stock decrement inside the same bill transaction; cancel/void restores it
  - 1% wallet debit from the cashier's wallet inside the same transaction
- Kitchen / counter order queue (`/pos/orders`, Livewire + `wire:poll`):
  - today's board with open/all filters, per-status counters
  - advance one step (pending → preparing → ready → completed), skip-ahead,
    or cancel/void from any live state (cancelled is terminal)
  - order-type filter splits dine-in / takeaway / parcel / delivery
  - same transitions exposed to the mobile app via
    `GET/PATCH /api/orders` (tenant-isolated)
- Stock control (per-item opt-in: `NULL stock_quantity` = untracked/unlimited):
  - stock count + low-stock threshold editable in MenuManager
  - low-stock alert listing items at/below threshold (+ sold-out flag)
  - POS blocks out-of-stock adds and caps the cart at shelf count;
    server re-checks under row locks so two cashiers can't oversell
  - cancelling a bill returns tracked quantities to the shelf
  - stock edits audited (`stock_updated`)
- Wallet system + Razorpay recharge + payment verification
- **Flutter mobile app** (`mobile/`, offline-first with local SQLite + outbox/sync):
  - auth, menu browsing, cart, order checkout, receipt screen, POS screen,
    wallet screen, Razorpay recharge
- Full Sanctum JSON API: menu read, admin-only menu writes, order placement
  (shared `OrderService`), queue list + status transitions, profile,
  wallet balance + recharge initiate/verify, `POST /api/bill/generate` alias
- Production reliability: validation, error handling, owner audit trail
  (18 audited actions), DB migrations + seeders, Docker setup,
  idempotent bill placement for offline retries, 43 tests / 145 assertions

**Still missing (the real next phase):**

- card / credit / split payment UI in the web POS (server + API accept them;
  web buttons are cash + UPI only)
- order-type + customer capture in the web POS UI (web POS sends no
  `order_type`, so every web bill settles as takeaway, and sends no customer
  name/phone — only discount + free-text bill note; API/mobile clients can
  send all four order types plus customer details)
- refunds beyond cancel-void, staff shifts, hourly sales / best-seller reports
- table management (no table-number field exists)

So the current stage = **Phase 1 (POS + billing) done, Phase 2 (operations: stock, kitchen queue, payments backbone) largely done**. The next work: card/credit/split POS UI, order-type selector, refunds, staff shifts, and deeper reports (hourly, best-sellers).

---

## 2. What you should focus on next

You do not need multi-branch, customer app, or online restaurant platform right now.

For your business idea, the next phase should be:

- make the system faster for a busy food outlet
- make billing and order handling more complete
- improve the staff workflow around selling food quickly

The main goal is: generate a bill, complete the order, and serve the customer smoothly.

---

## 3. The real business direction

Your business should be:

A POS and billing system for a high-volume food outlet where staff can quickly take orders, generate bills, and manage sales in real time.

This is much simpler and more realistic than a multi-branch or online ordering platform.

---

## 4. Feature status check (verified 14 Sep 2026)

Checked against the actual code — most of this list is **already built**.
Only the **bold** gaps in the ⚠️ rows are real remaining work.

| # | Feature | Status |
|---|---------|--------|
| 1 | Quick order entry (fast add, quantity controls, instant totals) | ✅ Done — `BillingDashboard` (search, category filter, steppers, live BCMath totals, F2/F4/F8/F9 shortcuts) |
| 2 | Bill generation (itemized bill, per-slab GST, discount, round-off, thermal print, notes) | ✅ Done — shared `OrderService` + printable receipt |
| 3 | Payment flow | ⚠️ Partial — server/API accept cash, upi, card, credit, split; **web POS buttons are cash + UPI only**; no change-calc or split-tender UI |
| 4 | Order status flow (new → preparing → ready → completed → cancelled) | ✅ Done — guarded transitions in `OrderService::updateStatus()`, tested |
| 5 | Kitchen / counter board (live queue, dine-in vs takeaway split) | ✅ Done — `/pos/orders` with `wire:poll`, type filter, per-status counters; same flow via `PATCH /api/orders/{id}/status` |
| 6 | Inventory tracking (stock count, low-stock alert, block out-of-stock) | ✅ Done — opt-in per item (`NULL` = untracked), low-stock alert in MenuManager, POS + server-side oversell guards, cancel restores stock |
| 7 | Sales reporting (daily sales, revenue, payment mix, recent bills) | ⚠️ Partial — daily KPIs + payment mix + recent bills + discounts + avg bill exist; **no hourly breakdown, no best-sellers, no date-range/export** |
| 8 | Table / takeaway / delivery handling | ⚠️ Partial — order types stored + filtered on the queue; **no table-number field, no order-type selector in web POS, no delivery-driver flow** |
| 9 | Staff role workflow | ⚠️ Partial — admin/cashier gates + middleware + deactivation block; **no staff-management CRUD UI, no shifts, no manager role** |
| 10 | Production-ready reliability | ⚠️ Partial — validation, error handling, audit logs, migrations/seeds, Docker, idempotency, 43 tests; **no backup story, no deployment runbook in repo** |

### The real "must add" list (only what's left)

1. Web POS: card / credit / split payment buttons + change calculation
2. Web POS: order-type selector (dine-in / takeaway / parcel / delivery)
3. Refunds beyond cancel-void (partial refund, reason, audit)
4. Reports: hourly sales, best-sellers, date range, export
5. Staff: management UI (create/deactivate), shifts
6. Tables: table numbers for dine-in
7. Ops: backup + deployment runbook

---

## 5. Best next phase for your project

The best next phase is:

Finish the POS gaps, then deepen reports — the engine (billing, queue, stock) is already built.

### Priority features (in build order)

1. Web POS card / credit / split buttons + change calculation
   (server + enums already accept all five modes — UI-only work)
2. Web POS order-type selector (dine-in / takeaway / parcel / delivery)
3. Refunds beyond cancel-void (partial refund + reason + audit row)
4. Reports: hourly sales, best-sellers, date range, export
5. Staff management UI + shifts
6. Table numbers for dine-in

This is the version that matches your idea of a rush food outlet business.

---

## 6. Recommended MVP scope (current status)

- ✅ admin logs in (role routing + deactivation block)
- ✅ add or update food items and prices (MenuManager + audit)
- ✅ staff creates new bill (keyboard-first POS)
- ✅ add menu items to bill (search, filter, steppers)
- ✅ apply quantity, discounts, or notes (clamped, server re-validated)
- ⚠️ choose payment mode (cash/UPI in web POS; card/credit/split via API only)
- ✅ print receipt (thermal dialog + mobile printer service)
- ✅ save sale and show in dashboard (sales summary)
- ✅ check daily sales summary (revenue, bills, payment mix, avg bill, discounts)
- ✅ order queue + status flow (`/pos/orders`, API too)
- ✅ stock control (counts, alerts, oversell guards)

Remaining for a genuinely complete outlet MVP: payment-mode buttons, order-type selector, refunds, hourly/best-seller reports, table numbers.

---

## 7. What not to build right now

Skip these for now:

- multi-branch support (note: the schema is already multi-store/tenant-scoped,
  but there is no multi-branch management UI — one store per owner account today)
- online ordering website
- customer app (note: the Flutter app is a *staff* app — billing, queue,
  wallet — not a customer self-ordering app)
- delivery driver system
- loyalty points (note: a staff wallet + Razorpay recharge + 1%-per-bill
  debit already exists; customer-facing loyalty would build on it)
- marketing platform
- franchise model

These are good later, but not necessary for the first version of a food outlet business.

---

## 8. Suggested business path

### Phase 1: POS and billing — ✅ done

- menu management
- order creation
- bill generation
- payment handling (engine; web UI cash/UPI)
- receipt printing
- sales summary

### Phase 2: operations — ✅ mostly done

- ✅ stock management
- ✅ kitchen order queue
- ✅ discounts (bill-level, audited)
- ❌ staff shifts
- ❌ refunds (beyond cancel-void)
- ⚠️ sales reports (daily done; hourly/best-sellers/export missing)

### Phase 3: scale

- table management
- delivery order workflow
- loyalty/discount campaigns
- more reports and analytics

---

## 9. Final advice

You already have more than the foundation — billing, kitchen queue, stock
control, wallet, audit trail, mobile staff app, and 43 passing tests are built.

What you should add next is not customer-facing complexity — it is finishing
the cashier counter: all payment buttons, order-type selector, refunds, and
the reports an owner checks every night (hourly, best-sellers).

The right next step is:

Make the web POS accept every payment mode and order type the engine already supports, then deepen reporting.

If the cashier can open the menu, add items, generate the bill, accept any payment, print receipt, and save the sale quickly — and the owner can see hourly and best-seller numbers — then the product is ready for real business use.

---

## 10. Simple mission statement

BizBite helps busy food outlets serve customers faster, generate accurate bills, and manage daily sales efficiently.

This is the cleanest and most realistic direction for your project.
