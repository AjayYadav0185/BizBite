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