# BizBite Offline-First POS — Operator & Developer Guide

This document is the single reference for how the Flutter POS behaves with
no network, flaky Wi-Fi, or a healthy connection.

## 1. Mental model (60 seconds)

```
Local SQLite = what the cashier sees. Server = where money settles.
Billing NEVER waits for network. Sync is eventual + idempotent.
```

- Menu grid paints from `cached_items` instantly, then refreshes behind.
- Checkout posts `POST /api/orders`; on timeout it queues the SAME payload
  into `pending_orders` and prints a `LOCAL-XXXX` receipt immediately.
- `SyncOrchestrator` replays the outbox FIFO with the same
  `idempotency_key`, so Laravel `OrderService` dedupes — no double bills.
- Status banner: Online / Offline (N queued) / Syncing / Failed + Retry.

## 2. Conflict rules

| Data | Rule | Where |
|---|---|---|
| Orders/payments | Append-only events, idempotent replay. Server recomputes ₹ | `OrderRepository`, `SyncOrchestrator`, `OrderService::place()` |
| Menu catalog | Server-wins LWW (wholesale cache replace) | `MenuRepository.fetchMenu()` + `MenuCacheDao.replaceAll()` |
| Stock | Operation deltas only (server decrements) | Backend (client never sends stock) |
| Price changed mid-offline | Server charges current price, flags drift for audit | `OrderService` (returns server total) |

CRDTs are intentionally NOT used — asymmetric trust (cashier creates facts,
server settles money) needs idempotency + LWW, not peer-to-peer merge.

## 3. Files

```
lib/core/sync/
  app_database.dart     # sqflite bizbite_pos.db (WAL) + meta kv
  menu_cache_dao.dart   # cached_categories / cached_items
  outbox_dao.dart       # pending_orders (claim/complete/backoff/failed)
  pending_order.dart    # outbox row model
  sync_controller.dart  # connectivity_plus + HEAD /api/user probe + banner
  sync_orchestrator.dart# push-then-pull engine (coalescing kick())
lib/presentation/widgets/sync_status_banner.dart
```

## 4. Manual test script (do this before release)

1. Sign in once (menu caches). Kill network (airplane mode).
2. Restart app offline → menu grid must still render from cache.
3. Bill 2 items → receipt shows `LOCAL-XXXXXXXX (offline)`, banner shows
   `Offline — 1 bill(s) queued`.
4. Close + reopen app still offline → banner still shows 1 queued
   (durability check — outbox survived restart).
5. Reconnect → banner flips to Syncing → Online; `pending_orders` empties.
6. Laravel `tbl_pos_orders`: exactly ONE row per idempotency_key:
   `SELECT idempotency_key, COUNT(*) c FROM tbl_pos_orders GROUP BY 1 HAVING c>1`
   must return zero rows.
7. Change a price on the web admin while a tablet is offline, then bill
   offline and reconnect → server total wins; receipt total updates on next
   pull (drift is auditable, never blocks the sale).

## 5. Backend contract (Laravel)

- `POST /api/orders` MUST stay idempotent on `idempotency_key` (already is
  via `OrderService` + `tbl_pos_sync_queue.idempotency_key UNIQUE`).
- `GET /api/menu` returns `{categories[], items[]}` with `price` as
  decimal string; client parses via `parse_utils.dart`.
- Recommended hardening: add `price_snapshot` JSON to `tbl_pos_orders` and
  return `price_drift: true` when the settled total differs from any client
  hint, so the receipt screen can show an amber badge.
- `HEAD /api/user` is used as the reachability probe (401 = reachable).
