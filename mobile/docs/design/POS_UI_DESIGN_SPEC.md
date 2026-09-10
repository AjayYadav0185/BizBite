# BizBite POS — Mobile UI/UX Design Spec

**Product:** BizBite mobile POS (`mobile/`) — restaurant management & point-of-sale
**Users:** Cashiers, waiters, managers in high-pressure, fast-paced service
**Status:** Implemented in Flutter — see the files referenced in each section.

---

## 1. Design System

Implementation: `lib/presentation/theme/bizbite_theme.dart`

### 1.1 Color

| Token | Hex | Usage |
|---|---|---|
| **Brand / Primary** | `#E65100` Deep Amber | Primary CTAs ("Process & Print Receipt"), quick-add "+", selected chips, money accents |
| **Brand Deep** | `#BF360C` | Pressed/emphasis states, in-cart "+" buttons, price text |
| **Brand Soft** | `#FFE0B2` | Selected chip tint, badges, icon washes |
| **Canvas** | `#F5F5F5` Soft off-white | App background — clean contrast under white cards |
| **Ink Dark** | `#1B1712` Warm dark | App bar shell, phone-mode bill bar (premium till feel) |
| **Success Green** | `#2E7D32` | Paid/settled orders, discount rows, "verified" tick |
| **Success Container** | `#E6F4EA` | Receipt screen status banner |
| **Alert Amber** | `#EF6C00` | Reserved for pending-kitchen states |
| **Hairline** | `#E4E0DC` | Card borders, dividers, disabled fills |

### 1.2 Typography

- System **Roboto** (Inter can be bundled later via `pubspec.yaml` fonts — the
  theme is a single swap point).
- **Bold numerals everywhere money or counts appear** —
  `BizBiteTheme.numeral` applies `FontFeature.tabularFigures()` + `w800`, so
  columns of prices never jitter while scanning a bill.
- Receipt typography: `BizBiteTheme.receiptMono()` — monospaced
  (RobotoMono → Menlo → Courier fallback chain) to mimic thermal output.
  *For a pixel-exact match, bundle `RobotoMono` under `mobile/assets/fonts/`
  and declare it in `pubspec.yaml`.*

### 1.3 Shape, spacing & elevation

- Corner radii: **12dp** (chips, steppers, thumbs) · **14dp** (cards, inputs) ·
  **16dp** (buttons, panels) · **18–24dp** (bill bar, sheets).
- Base grid: 4dp; screen gutters 12–16dp; intra-card padding 10–12dp.
- Elevation ≈ 0 everywhere — separation uses hairline borders and surface
  tints (fast rendering, no muddy shadows).
- Tap targets: primary actions ≥ 48dp tall (Process & Print = 56dp, footer
  buttons = 54dp); the whole food card is a tap target; steppers are 32dp
  pills (secondary, adjacent targets spaced 10dp+).

---

## 2. Screen 1 — Home Dashboard / Quick Order Grid

Files: `lib/presentation/screens/pos_screen.dart` (composition),
`lib/presentation/screens/menu_pane.dart` (grid).

```
┌──────────────────────────────────────────┐
│ ███ Dark app bar — Store name / Pay Desk │  ← logout icon
├──────────────────────────────────────────┤
│ [ Dine-In │ Takeaway │ Delivery │ Parcel]│  ← SegmentedButton order type
│ ┌──────────────────────────────────────┐ │    writes cart.orderType
│ │ 🔍 Search menu…                      │ │  ← filled white, 14dp radius
│ └──────────────────────────────────────┘ │
│ (All) (Snacks) (Beverages) (Desserts) →  │  ← horizontal category chips,
├──────────────────────────────────────────┤    icon + label, amber when active
│ ┌─────────────┐  ┌─────────────┐         │
│ │ ▒▒ thumb ▒▒ │  │ ▒▒ thumb ▒▒ │         │  ← 84dp tinted thumb,
│ │        (2)▜ │  │             │         │    warm hue per category,
│ │ Vada Pav    │  │ Masala Chai │         │    qty badge when in cart
│ │ ₹12.00   ⊕  │  │ ₹20.00   ⊕  │         │  ← bold tabular price + 34dp "+"
│ └─────────────┘  └─────────────┘         │
│ … 2-column grid (more columns ≥ tablet)… │
├──────────────────────────────────────────┤
│ ▐ 3 item(s) on bill   ₹56.00  [🖨 Print]▌│  ← Screen 2 bill bar (below)
└──────────────────────────────────────────┘
```

**Decisions**

- **One-tap ordering:** tapping anywhere on a card adds the item; the "+"
  button is a visual amplifier, not the only target — critical for speed.
- In-cart items get a 1.4dp amber border + a white-ringed count badge so a
  cashier can verify the order at a glance without opening the bill.
- Category hue-coding (warm cream/rose/mint/butter/lavender/aqua thumbnails)
  gives per-family visual anchors; keyword-matched food glyphs stand in for
  images until the backend serves `image_url`.
- Order-type toggle includes **Parcel** because `POST /api/orders` validates
  `dine_in,takeaway,parcel,delivery` — dropping it would strand the flow.
- Empty search results get a friendly "No items match this filter" state.

---

## 3. Screen 2 — Cart & Billing Summary (POS View)

Files: `lib/presentation/screens/cart_pane.dart` (pane),
`lib/presentation/screens/pos_screen.dart` (responsive shell).

**Responsive strategy**

| Layout | Width | Bill presentation |
|---|---|---|
| Split-screen | ≥ 840dp (tablet/landscape) | Fixed 380dp side-sheet panel, always visible |
| Bill bar + sheet | < 840dp (phone) | Persistent dark bill bar; full bill opens as a 92%-height modal bottom sheet |

**Phone bill bar** (warm dark `#1B1712`, 18dp radius): live item count,
grand total in 19dp tabular numerals, current order-type chip, and an amber
"Process & Print" button — the bar *is* the checkout affordance; tapping the
summary region opens the detailed bill.

**Bill sheet / side panel anatomy (top → bottom):**

```
┌ Current Bill ─────────────── 3 items · 2 lines ─ Clear ┐
│ Vada Pav            (−) 2 (+)              ₹24.00      │
│   ✎ No onion — extra spicy        ← per-line note      │
│ Masala Chai         (−) 1 (+)              ₹20.00      │
│   ✎ Add note                                          │
│ PAYMENT  (Cash) (UPI) (Card) (Credit) (Split)         │
│   UPI transaction ref ___________  ← only when UPI    │
│ ▸ Customer & discount (optional)  ← collapsed by      │
│     default: name / phone / discount ₹                │
│ Subtotal                                        ₹44.00│
│ Discount                                       −₹4.00 │  ← success green
│ Tax breakdown                       ← hook reserved*  │
╞══ FIXED FOOTER ═══════════════════════════════════════╡
│ GRAND TOTAL                        ✓                  │
│ ₹40.00  (25dp w800 tabular)                           │
│ ┌───────────────────────────────────────────────────┐ │
│ │  🖨  PROCESS & PRINT RECEIPT      (56dp, amber)    │ │
│ └───────────────────────────────────────────────────┘ │
└───────────────────────────────────────────────────────┘
```

**Decisions**

- Quantity steppers are 32dp stadium pills; "−" at qty 1 removes the line
  (matches `CartController.setQuantity` semantics) — no separate delete
  cluttering the row.
- Kitchen notes are stored on `CartLine.note` (UI-only today; the current
  `POST /api/orders` wire contract has no line-note field — see
  `cart_line.dart` for the forward-compatibility note).
- Customer/discount fields collapse into an ExpansionTile — rush-hour
  bills stay scannable; the fields remain one tap away.
- *Tax rows:* the backend receipt payload currently exposes only
  subtotal/discount/total. The totals section is structured so tax rows
  render automatically once `POST /api/orders` returns a tax breakdown.
- Settle failures surface both inline (banner) and as a snackbar so the
  error is visible from inside the modal sheet too.

---

## 4. Screen 3 — Receipt Preview (Thermal Printer Optimized)

Files: `lib/presentation/screens/receipt_screen.dart`,
`lib/presentation/widgets/thermal_paper.dart` (TearEdge + DashedDivider).

```
┌────────────────────────────────────────────┐  dark "printer bed" #161310
│ ✅ Payment received                        │  success-green banner card
│    Cash · Bill #001-20260908-0042          │
│                                            │
│              ▽▽▽ torn edge ▽▽▽            │  ← jagged tear (CustomPaint)
│ ┌────────────────────────────────────────┐ │
│ │            BIZBITE POS                 │ │  print header (spaced caps)
│ │        Demo Store                      │ │  17dp mono w800
│ │     123 Main Street · Ph: …            │ │  muted mono
│ │  - - - - - - - - - - - - - - - - - -   │ │  dashed perforation divider
│ │  Bill #: 001-20260908-0042  (bold)     │ │
│ │  Date: 08 Sep 2026, 12:45 PM           │ │
│ │  Cashier: Cashier User                 │ │
│ │  Order: Takeaway                       │ │
│ │  - - - - - - - - - - - - - - - - - -   │ │
│ │  ITEM      QTY   AMOUNT                │ │
│ │  Vada Pav   2x                 Rs.24.00│ │
│ │  Masala Chai 1x                Rs.20.00│ │
│ │  - - - - - - - - - - - - - - - - - -   │ │
│ │  TOTAL (3 items)           Rs.44.00    │ │  ← 19dp mono w800
│ │  PAID VIA                     Cash     │ │
│ │        ┌─────────┐                     │ │
│ │        │ QR 92dp │  BIZBITE|bill#|amt  │ │
│ │        └─────────┘                     │ │
│ │      Scan to verify · <bill#>          │ │
│ │      Thank you! / Powered by BizBite   │ │
│ └────────────────────────────────────────┘ │
│              △△△ torn edge △△△            │
│                                            │
│ ┌─ Print Bill via Bluetooth ─┐ ┌─ New Order ─┐ │  ← 54dp footer buttons
│ └────────────────────────────┘ └─────────────┘ │
└────────────────────────────────────────────┘
```

**Decisions**

- 320dp paper width ≈ an 80mm roll at logical pixel scale; content padding
  18dp mirrors real thermal margins.
- **Jagged tear edges** are a filled zig-zag `CustomPainter` (`TearEdge`,
  irregular tooth heights for realism), hugging the white sheet top & bottom.
- The on-screen preview intentionally mirrors the ESC/POS generator in
  `receipt_printer.dart` (same store header/footer, `Rs.` amounts, meta
  rows) so what the cashier sees is what prints.
- QR payload `BIZBITE|<order number>|<total>` is machine-verifiable and
  cheap to render with `qr_flutter` (square eyes/modules, near-black ink).
- Footer: white-on-dark **"Print Bill via Bluetooth"** (shows a spinner +
  status line in the green banner while printing) and amber **"New Order"**
  (clears the receipt and returns to the grid via
  `orderFlow.clearReceipt()`).

---

## 5. Screen 4 — Store Console

File: `lib/presentation/screens/admin_screen.dart` (the "Store" tab).
Shared visuals: `lib/presentation/widgets/category_visuals.dart` — the exact
same category hue-coding and glyphs used by the POS grid, so "Beverages"
looks the same on both tabs.

```
┌──────────────────────────────────────────┐
│ ████████████████████████████████████████ │  dark hero card (20dp radius)
│ █ [B]  Demo Store                        █
│ █      MG Road, Bengaluru                █
│ █  📞 +91 98XXX XXXXX                    █
│ █  ▣ store@upi              [⧉ copy]    █  ← tap-to-copy UPI VPA
│ █  (₹ INR) (GSTIN 29ABCDE1234F1Z5) …     █  ← FSSAI chip when present
│ ████████████████████████████████████████ │
│ ┌──────────────────────────────────────┐ │
│ │ [A]  Amit Sharma            ADMIN   │ │  ← staff card: avatar, name,
│ │      amit@example.com               │ │    email, role badge (amber for
│ └──────────────────────────────────────┘ │    admin / neutral for cashier)
│ Menu overview                            │
│ ┌──────────────────────────────────────┐ │
│ │ ▒ Beverages        12 item(s)      ▾ │ │  ← expandable category card,
│ │   Masala Chai                 ₹20.00 │ │    icon tinted per category,
│ │   Cold Coffee                 ₹60.00 │ │    price in bold tabular figures
│ └──────────────────────────────────────┘ │
│ Full menu & store editing lives in the   │
│ web console.                             │
└──────────────────────────────────────────┘
```

**Decisions**

- Dark hero mirrors the app-bar shell so the Store tab feels like part of
  the same premium till, not a bolted-on admin page.
- UPI VPA copy affordance: cashiers share the store ID with walk-in
  customers daily — one tap + a confirmation snackbar.
- Role badge = session accountability: the till always shows who is signed
  in (colored amber only for admins so ownership is obvious).
- Category cards are collapsed expansions — a 200-item menu stays a short
  scroll instead of an endless list; expanding shows *all* items (the old
  UI truncated at 8).

---

## 6. Motion & feedback (fatigue prevention)

- `InkSparkle` splash + 160ms animated chip tint — perceptible feedback with
  zero bounce delays.
- Optimistic cart updates: menu cards badge instantly from
  `CartController.notifyListeners()`.
- Stable layout: the bill bar is always present (disabled when empty) so
  muscle memory for the checkout position never re-learns.
- Monochrome surfaces + a single accent color: the eye only has to hunt for
  **amber = money/CTA**, **green = paid**, **red = error**.

---

## 7. Accessibility & ergonomics

- Body text ≥ 13sp; prices/totals 14–25sp bold; all text ≥ 4.5:1 on their
  backgrounds (white-on-amber CTAs use `#E65100` with `w800` weight).
- All destructive actions (Clear) are text-buttons colored `scheme.error`,
  away from the primary CTA.
- Single-hand reach: the checkout CTA lives at the bottom of every layout
  (bill bar / sheet footer / side panel footer).
