# BizBite — Focused Next Phase Plan

> Last verified against the codebase: **15 Sep 2026** — `php artisan test` → **54 passed (208 assertions)**.

## 0. Current project stage (live update)

The core POS, billing system **and the entire operations layer from the previous
"must add" list are built and working**. Phase 1 and Phase 2 are complete, and
large parts of Phase 3 (tables, delivery workflow, discount campaigns) are
already in.

**What is already built and running:**

- Laravel 11 backend (Livewire 3, Sanctum 4) with native PHP enums and typed models
- Multi-store (tenant) architecture — every table is store-scoped via `StoreScope`
- Admin portal (Laravel Livewire):
  - role login (admin / cashier split)
  - menu manager (categories, food items, prices, veg/non-veg/egg, GST slabs)
  - sales summary (today's revenue, bill count, payment-mode mix, recent bills)
  - receipt customizer (print header/footer for bills)
  - audit log viewer (owner audit trail — 19 audited actions)
  - reports panel — hourly sales, best-sellers, date-range totals, one-click
    CSV export of the range's bills
  - table manager (dining tables: number + seats)
  - campaign manager (discount campaigns redeemable by code at the POS)
  - staff manager (create/update cashier accounts)
- POS billing dashboard (Laravel Livewire, keyboard-first):
  - live search + category filter menu grid
  - instant add / quantity steppers / cart editing
  - live totals (BCMath, GST computed per item slab, discount, round-off)
  - **all five payment modes as buttons — cash, UPI, card, credit, split**
    (split takes multiple tender legs; cash shows tendered amount and live
    change-due, both stored on the bill)
  - **order-type selector** (dine-in / takeaway / parcel / delivery) +
    table-number capture for dine-in + customer name/phone capture
  - campaign code redemption (engine-validated discount)
  - checkout via shared `OrderService`, native thermal print dialog, receipt data
  - shortcuts: F2 clear cart, F4 focus search, F8 cash checkout, F9 UPI checkout
- Order engine (`OrderService`, used identically by web POS, Sanctum API and
  the mobile app):
  - subtotal / discount / campaign discount / GST-per-item-slab / round-off /
    total (BCMath)
  - invoice number + per-store-per-day order number, idempotency key, UPI reference
  - order types (dine_in, takeaway, parcel, delivery) and statuses
    (pending → preparing → ready → completed, plus cancelled)
  - payment modes enum (cash, upi, card, credit, split) with per-order
    `payments` ledger row, persisted split legs, tendered + change amounts
  - `table_number`, `campaign_code`, `delivery_address` / `delivery_agent` /
    `delivery_status` columns on every order
  - stock decrement inside the same bill transaction; cancel/void restores it
  - 1% wallet debit from the cashier's wallet inside the same transaction
- Kitchen / counter order queue (`/pos/orders`, Livewire + `wire:poll`):
  - today's board with open/all filters, per-status counters
  - advance one step (pending → preparing → ready → completed), skip-ahead,
    or cancel/void from any live state (cancelled is terminal)
  - order-type filter splits dine-in / takeaway / parcel / delivery
  - side panel per bill for **partial refunds** (amount + mode + mandatory
    reason) and **delivery moves** (assign agent, advance delivery status)
  - same transitions exposed to the mobile app via `GET/PATCH /api/orders`
- Refunds beyond cancel-void (`RefundService`): refunded total can never exceed
  the bill, every refund writes a Refund ledger row + a negative payment leg +
  an owner audit row, atomically; same flow via `POST /api/orders/{order}/refund`
- Staff shifts (`ShiftService`, `/pos/shift` panel): one open shift per staff
  member, opening cash counted at handover, expected cash = opening + cash
  settled during the shift, closing variance (short/over) visible to the owner;
  API: `GET /api/shifts`, `POST /api/shifts/open`, `POST /api/shifts/{shift}/close`
- Reports (`ReportService`, admin dashboard panel + API): hourly sales for a
  day, best-sellers by quantity/revenue for a range, date-range totals, CSV
  export; API: `/api/reports/hourly`, `/api/reports/best-sellers`, `/api/reports/range`
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
  - ops feature set: order queue, refunds (online only — never queued),
    shifts (open/close queued), reports (cached numbers), and a console for
    tables / campaigns / staff (edits queued + cache patched) — see
    `mobile/docs/OFFLINE_FIRST.md`
- Full Sanctum JSON API: menu read, admin-only menu writes, order placement
  (shared `OrderService`), queue list + status transitions, refunds, delivery
  moves, shifts, reports, tables CRUD, campaigns CRUD, staff CRUD (admin),
  profile, wallet balance + recharge initiate/verify, `POST /api/bill/generate` alias
- Production reliability: validation, error handling, owner audit trail
  (19 audited actions), DB migrations + seeders, Docker setup,
  idempotent bill placement for offline retries, 54 tests / 208 assertions
  (including `NextPhaseTest`: every payment mode + order type, split-tender
  persistence, campaign discount, refund guards, and the delivery / shift /
  report / table / staff flows)

**Still missing (the real next phase):**

- backup + restore story and a deployment runbook (the repo README is still the
  stock Laravel scaffold; no DB backup schedule, no deploy checklist)
- loyalty points / customer identity (campaign discounts exist for staff use;
  there is no customer account or points accrual)
- multi-branch support (schema is already multi-store/tenant-scoped, but there
  is no branch-management UI — one store per owner account today)
- customer self-ordering website/app and a dedicated delivery-driver app
  (delivery orders carry agent + status, but there is no driver experience)

So the current stage = **Phase 1 (POS + billing) done, Phase 2 (operations:
stock, kitchen queue, refunds, shifts, reports, staff) done, Phase 3 (scale:
tables, delivery workflow, discount campaigns) largely done**. The next work is
production hardening (backup + deployment runbook), then customer-facing growth
(loyalty, online ordering, multi-branch).

---

## 2. What you should focus on next

You do not need more counter features — the cashier workflow is complete.
The next phase is about two things:

- **hardening what exists for real business use**: backups, deployment
  runbook, monitoring — the things that only hurt when they are missing
- **customer-facing growth**: loyalty points, online ordering, multi-branch —
  now that the in-store engine can carry the load

The main goal is no longer "generate a bill" (done) — it is "run the outlet
reliably every day, and grow past the first counter".

---

## 3. The real business direction

Your business should be:

A POS and billing system for a high-volume food outlet where staff can quickly take orders, generate bills, and manage sales in real time.

This is much simpler and more realistic than a multi-branch or online ordering platform.

---

## 4. Feature status check (verified 15 Sep 2026)

Checked against the actual code — the previous gaps are all closed.
Only the **bold** gaps in the ⚠️ rows are real remaining work.

| # | Feature | Status |
|---|---------|--------|
| 1 | Quick order entry (fast add, quantity controls, instant totals) | ✅ Done — `BillingDashboard` (search, category filter, steppers, live BCMath totals, F2/F4/F8/F9 shortcuts) |
| 2 | Bill generation (itemized bill, per-slab GST, discount, round-off, thermal print, notes) | ✅ Done — shared `OrderService` + printable receipt |
| 3 | Payment flow | ✅ Done — all five modes as web POS buttons (cash, UPI, card, credit, split with multiple tender legs), tendered + change-due calc, `payments` ledger + split legs persisted |
| 4 | Order status flow (new → preparing → ready → completed → cancelled) | ✅ Done — guarded transitions in `OrderService::updateStatus()`, tested |
| 5 | Kitchen / counter board (live queue, dine-in vs takeaway split) | ✅ Done — `/pos/orders` with `wire:poll`, type filter, per-status counters, refund + delivery side panel; same flow via API |
| 6 | Inventory tracking (stock count, low-stock alert, block out-of-stock) | ✅ Done — opt-in per item (`NULL` = untracked), low-stock alert in MenuManager, POS + server-side oversell guards, cancel restores stock |
| 7 | Sales reporting | ✅ Done — daily summary + hourly breakdown + best-sellers + date-range totals + CSV export (`ReportService`, admin panel, `/api/reports/*`) |
| 8 | Table / takeaway / delivery handling | ✅ Done (core) — order-type selector + table-number capture in web POS, `TableManager`, delivery address/agent/status + queue moves; **no dedicated driver app** |
| 9 | Staff role workflow | ✅ Done (core) — `StaffManager` CRUD UI + API, shifts with opening/expected/closing cash variance (`ShiftService`, `/pos/shift`); **no separate manager role** (admin/cashier only) |
| 10 | Production-ready reliability | ⚠️ Partial — validation, error handling, audit logs (19 actions), migrations/seeds, Docker, idempotency, 54 tests; **no backup story, no deployment runbook in repo** |

### The real "must add" list (only what's left)

1. Ops: backup + restore story + deployment runbook (README is still stock Laravel)
2. Loyalty: customer identity + points accrual (staff-side discount campaigns already exist)
3. Multi-branch: branch management UI on the existing tenant schema
4. Customer ordering: online ordering website/app
5. Delivery: dedicated driver app (delivery status tracking already exists)

---

## 5. Best next phase for your project

The best next phase is: **production hardening first, then customer-facing
growth** — the product itself (counter + operations) is feature-complete.

### Priority features (in build order)

1. Backup + restore story (scheduled DB dumps, tested restore) and a
   deployment runbook in the repo (replacing the stock Laravel README)
2. Loyalty points: customer identity + accrual, building on the existing
   campaign-discount engine and wallet infrastructure
3. Multi-branch management UI on the existing multi-store tenant schema
4. Online ordering website/app for customers
5. Dedicated delivery-driver app (delivery status tracking already exists)

This is the version that matches your idea of a rush food outlet business —
and it is already built.

---

## 6. Recommended MVP scope (current status)

- ✅ admin logs in (role routing + deactivation block)
- ✅ add or update food items and prices (MenuManager + audit)
- ✅ staff creates new bill (keyboard-first POS)
- ✅ add menu items to bill (search, filter, steppers)
- ✅ apply quantity, discounts, or notes (clamped, server re-validated)
- ✅ choose payment mode (all five: cash / UPI / card / credit / split + change calc)
- ✅ print receipt (thermal dialog + mobile printer service)
- ✅ save sale and show in dashboard (sales summary)
- ✅ check daily sales summary (revenue, bills, payment mix, avg bill, discounts)
- ✅ order queue + status flow (`/pos/orders`, API too)
- ✅ stock control (counts, alerts, oversell guards)
- ✅ order types + table numbers + customer capture (web POS + API)
- ✅ partial refunds with reason + audit (queue side panel + API)
- ✅ staff shifts with cash-drawer variance (`/pos/shift` + API)
- ✅ hourly / best-seller / date-range reports + CSV export
- ✅ staff management UI (create/update cashiers)
- ✅ discount campaigns redeemable by code

**The outlet MVP is complete.** Remaining work is operational (backup/deploy)
or growth (loyalty, online ordering, multi-branch).

---

## 7. What not to build right now

Skip these for now:

- multi-branch support (note: the schema is already multi-store/tenant-scoped;
  build the branch-management UI only when a second outlet is real)
- online ordering website
- customer app (note: the Flutter app is a *staff* app — billing, queue,
  wallet, ops console — not a customer self-ordering app)
- delivery driver system (note: delivery orders already carry agent +
  status and can be moved from the queue; a driver app is the remaining piece)
- full loyalty platform (note: campaign discounts + staff wallet already exist;
  customer loyalty would build on them)
- marketing platform
- franchise model

These are good next, but only after the backup/deploy hardening is done —
they grow the business, hardening protects it.

---

## 8. Suggested business path

### Phase 1: POS and billing — ✅ done

- menu management
- order creation
- bill generation
- payment handling (all five modes in the web POS + API + mobile)
- receipt printing
- sales summary

### Phase 2: operations — ✅ done

- ✅ stock management
- ✅ kitchen order queue
- ✅ discounts (bill-level + campaign codes, audited)
- ✅ staff shifts (cash-drawer sessions with variance)
- ✅ refunds (partial refund, reason, audit row, ledger)
- ✅ sales reports (daily + hourly + best-sellers + date range + CSV export)

### Phase 3: scale — 🔶 largely done

- ✅ table management (dining tables + table-number capture on bills)
- ✅ delivery order workflow (address/agent/status + queue moves)
- ✅ discount campaigns (create in admin, redeem by code at the POS)
- ✅ more reports and analytics (hourly, best-sellers, range, export)
- ❌ loyalty points / customer identity
- ❌ multi-branch management UI
- ❌ customer-facing ordering app
- ❌ dedicated driver app

### Phase 4: hardening + growth (the actual next phase)

- backup + restore story, deployment runbook, monitoring
- loyalty points on top of the campaign + wallet infrastructure
- multi-branch, online ordering, driver app — in that order of readiness

---

## 9. Final advice

You are past the foundation and past the gap-filling — billing, kitchen queue,
stock control, wallet, audit trail, refunds, shifts, reports with CSV export,
staff + table + campaign management, the offline-first mobile staff app, and
**54 passing tests** are all built and wired into the web portal, the API, and
the mobile app.

The product is ready for real business use. What you should add next is not
more counter features — it is protecting what runs the outlet, then growing it:

1. **Backups + deployment runbook** — the only truly dangerous gap left.
   If the server dies tonight, there is no restore story in the repo.
2. **Loyalty points** — the campaign engine and wallet give you the rails.
3. **Multi-branch / online ordering / driver app** — only when the business
   demands them; the schema and engine are already ready for each.

The right next step is:

Write the backup schedule and deployment runbook first — then start loyalty.

A product this complete loses more money to an un-restorable crash than to any
missing feature.

---

## 10. Simple mission statement

BizBite helps busy food outlets serve customers faster, generate accurate bills, and manage daily sales efficiently.

This is the cleanest and most realistic direction for your project.
