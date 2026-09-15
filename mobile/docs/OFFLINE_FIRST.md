# BizBite Offline-First POS — Operator & Developer Guide

This document is the single reference for how the Flutter POS behaves with
no network, flaky Wi-Fi, or a slow backend.

## 1. Mental model (60 seconds)

```
Local SQLite = what the cashier sees. Server = where money settles.
Every screen works with zero bars. Sync is eventual + idempotent.
```

- The POS grid paints from `cached_items` instantly, then refreshes behind.
- Checkout posts `POST /api/orders`; on timeout it queues the SAME payload
  into `pending_orders` and prints a `LOCAL-XXXX` receipt immediately.
- Every OTHER screen paints from `cache_entries` (last-good RAW API
  envelopes) and every OTHER write goes into `pending_mutations`.
- `SyncOrchestrator` replays both outboxes FIFO, then pulls every screen's
  cache. Billing/work NEVER waits for network.
- Status strips: banner on the POS shell + an offline strip on every pushed
  screen — Online / Offline (N bills + M changes queued) / Syncing /
  Failed + Retry, plus an amber "Showing saved data" note wherever SQLite
  is painted instead of fresh server data.

## 2. What works offline (per screen)

| Screen | Offline read | Offline write |
|---|---|---|
| Billing (POS) | menu from `cached_items` | bills queued (idempotency key; server recomputes ₹) |
| Orders queue | cached queue per filter combo; own queued bills as `(queued)` cards | status / delivery moves queued + applied locally |
| Shifts | cached session list | open / close queued + local placeholder row |
| Reports | last cached numbers for the selected date/range | n/a (read-only) |
| Console (tables/campaigns/staff) | cached lists | edits / creates / deletes queued + cache patched |
| Menu manager | cached catalog | adds / edits / deletes queued + cache patched (`id < 0` placeholders) |
| Wallet | last cached balance + history | recharge needs a connection (Razorpay) |
| Refunds | — | **online only** (see §3) |

## 3. Conflict + trust rules

| Data | Rule | Where |
|---|---|---|
| Orders/payments | Append-only events, idempotent replay. Server recomputes ₹ | `OrderRepository`, `SyncOrchestrator`, `OrderService::place()` |
| Screen caches | Server-wins LWW (pull overwrites each envelope wholesale) | `CacheDao` + `SyncOrchestrator._pullScreenCaches()` |
| Offline edits | Optimistic local patch, server authoritative on replay | repository `patchWhere`/`upsertWhere` calls |
| Stock | Operation deltas only (server decrements) | Backend (client never sends stock) |
| Price changed mid-offline | Server charges current price, flags drift for audit | `OrderService` (returns server total) |
| Refunds / recharge | NEVER queued: replaying money after a timeout the server processed would double-debit | `OpsRepository.refund`, `WalletRepository` |
| Queued-work ownership | Every outbox row is stamped with `store_id`; a tablet re-signed into another store never replays foreign work | `OutboxDao` / `MutationOutboxDao` `_ownedBy` |
| Sign-out | Read caches wiped; outboxes KEPT (store-stamped — they resurface for their own store) | `BizBiteApp._onSessionChanged` |

CRDTs are intentionally NOT used — asymmetric trust (cashier creates facts,
server settles money) needs idempotency + LWW, not peer-to-peer merge.

## 4. Files

```
lib/core/sync/
  app_database.dart        # sqflite bizbite_pos.db v2 (WAL) + meta kv
  cache_dao.dart           # `cache_entries` — raw-envelope cache per endpoint
  cache_keys.dart          # keys (`queue.open.dine_in`, `report.range.…`)
  cache_patch.dart         # pure optimistic list-patch helpers (unit-tested)
  menu_cache_dao.dart      # `cached_categories` / `cached_items` + local edits
  mutation_outbox_dao.dart # `pending_mutations` (claim/replay/backoff/failed)
  offline_gateway.dart     # readRaw() reads + queueWhenOffline() writes
  offline_queued_exception.dart # SUCCESS signal for queued writes
  offline_sources.dart     # OfflineSources + OfflineStatusSink contract
  outbox_dao.dart          # `pending_orders` (bills) + `pendingBills()`
  pending_mutation.dart    # queued non-bill row model
  pending_order.dart       # queued bill row model (+ `total_amount`)
  sync_controller.dart     # connectivity + banner + counts + stale flags
  sync_orchestrator.dart   # push-bills → push-changes → pull-caches engine
lib/presentation/widgets/offline_screen_header.dart  # banner + cached strip
```

## 5. DB versions

- v1 = `meta`, `cached_categories`, `cached_items`, `pending_orders`.
- v2 = + `cache_entries`, `pending_mutations`, and
  `pending_orders.total_amount` / `pending_orders.store_id` /
  `pending_mutations.store_id`.
- `onUpgrade` migrates existing tablets in place — queued bills survive a
  mid-service app update.

## 6. Manual test script (do this before release)

### Billing (existing path)
1. Sign in once (menu caches). Kill network (airplane mode).
2. Restart app offline → menu grid must still render from cache.
3. Bill 2 items → receipt shows `LOCAL-XXXXXXXX (offline)`, banner shows
   `Offline — 1 bill(s) queued, work continues`.
4. Close + reopen app still offline → banner still shows 1 queued
   (durability check — outbox survived restart).
5. Reconnect → banner flips to Syncing → Online; `pending_orders` empties.
6. Laravel `tbl_pos_orders`: exactly ONE row per idempotency_key:
   `SELECT idempotency_key, COUNT(*) c FROM tbl_pos_orders GROUP BY 1 HAVING c>1`
   must return zero rows.
7. Change a price on the web admin while a tablet is offline, then bill
   offline and reconnect → server total wins; receipt total updates on next
   pull (drift is auditable, never blocks the sale).

### Every other screen (new in v2)
8. Offline (or throttle the server): open Orders / Shifts / Reports / Console
   / Wallet — each must paint its last-good cache with the amber
   "Showing saved data" strip instead of an error spinner.
9. While offline: advance a bill to Preparing in the queue, open a shift,
   flip a table to Occupied. Each grid/card updates immediately; the banner
   shows `M change(s) queued`.
10. Reconnect: the banner flips to Syncing → Online; verify on the server
    that the status / shift / table actually landed (exactly once).
11. Offline refund → must NOT queue: the screen shows
    "Refunds need a connection — reconnect and try again."
12. Offline wallet recharge → same treatment
    ("Recharge needs a connection — reconnect and try again."), while the
    cached balance + ledger history still renders.
13. Poison-row check: queue a status move for a bill the owner voids on the
    web, then reconnect — the mutation is parked as `failed` (banner shows
    it) instead of retrying forever.
14. Sign out while queued work exists, sign back in as the SAME store →
    cached screens refresh and the queued work replays. Sign in as ANOTHER
    store → the previous store's queued work never replays (it resurfaces
    only when its own store signs back in).

## 7. Backend contract (Laravel)

- `POST /api/orders` MUST stay idempotent on `idempotency_key` (already is
  via `OrderService` + `tbl_pos_sync_queue.idempotency_key UNIQUE`).
- `GET /api/menu` returns `{categories[], items[]}` with `price` as
  decimal string; client parses via `parse_utils.dart`.
- Every screen cache is a RAW envelope replay — the client never depends on
  undocumented fields for rendering, only for display.
- Recommended hardening: add `price_snapshot` JSON to `tbl_pos_orders` and
  return `price_drift: true` when the settled total differs from any client
  hint, so the receipt screen can show an amber badge.
- `HEAD /api/user` is used as the reachability probe (401 = reachable);
  client timeouts are 8s / 15s / 20s so a SLOW server fails over to cache
  quickly instead of hanging the till.

