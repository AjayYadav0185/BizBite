import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../../core/utils/india.dart';
import '../../features/orders/cart_controller.dart';
import '../../features/orders/data/models/cart_line.dart';
import '../../features/orders/data/models/order_models.dart';
import '../../features/orders/data/models/store_model.dart';
import '../../features/wallet/wallet_controller.dart';
import '../theme/bizbite_theme.dart';
import '../widgets/amount.dart';
import '../widgets/status_banner.dart';

/// Text controllers for the bill form, owned by [PosScreen] so typed
/// values survive the cart pane being rebuilt / reopened as a sheet.
class CartFields {
  TextEditingController discount = TextEditingController();
  TextEditingController customer = TextEditingController();
  TextEditingController phone = TextEditingController();
  TextEditingController upiRef = TextEditingController();
  TextEditingController table = TextEditingController();
  TextEditingController coupon = TextEditingController();
  TextEditingController notes = TextEditingController();
  TextEditingController tendered = TextEditingController();
  TextEditingController delivery = TextEditingController();

  List<TextEditingController> get all =>
      [discount, customer, phone, upiRef, table, coupon, notes, tendered, delivery];

  /// Re-sync the fields from the cart (after Resume / settle / clear).
  void loadFrom(CartController cart) {
    void put(TextEditingController c, String v) {
      if (c.text != v) c.text = v;
    }

    put(discount, cart.discountAmount > 0 ? cart.discountAmount.toStringAsFixed(0) : '');
    put(customer, cart.customerName);
    put(phone, cart.customerPhone);
    put(upiRef, cart.upiRef);
    put(table, cart.tableNumber);
    put(coupon, cart.campaignCode);
    put(notes, cart.notes);
    put(tendered, cart.tenderedAmount > 0 ? cart.tenderedAmount.toStringAsFixed(0) : '');
    put(delivery, cart.deliveryAddress);
  }

  void dispose() {
    for (final c in all) {
      c.dispose();
    }
  }
}

/// Screen 2 — the bill (side panel on tablets, bottom sheet on phones).
///
/// Order type → customer / table → lines with steppers + kitchen notes →
/// coupon, discount & note → payment (cash quick-notes + change, UPI QR,
/// split legs) → fixed footer with the rounded payable and Settle / Hold.
class CartPane extends StatelessWidget {
  const CartPane({
    super.key,
    required this.cart,
    required this.fields,
    required this.error,
    required this.settling,
    required this.onSettle,
    this.onHold,
    this.store,
    this.wallet,
  });

  final CartController cart;
  final CartFields fields;
  final String error;
  final bool settling;
  final VoidCallback? onSettle;
  final VoidCallback? onHold;

  /// Store profile — UPI ID for the QR, GST flag for the "incl. GST" hint.
  final StoreModel? store;
  final WalletController? wallet;

  static const _label = TextStyle(
      fontSize: 11.5,
      fontWeight: FontWeight.w800,
      letterSpacing: 0.8,
      color: AppColors.muted);

  @override
  Widget build(BuildContext context) {
    final hasItems = !cart.isEmpty;
    return Column(
      children: [
        _header(context, hasItems),
        StatusBanner(text: error, error: true),
        Expanded(
          child: ListView(
            padding: const EdgeInsets.fromLTRB(16, 4, 16, 16),
            children: [
              _orderTypeSelector(),
              const SizedBox(height: 10),
              _customerRow(),
              if (cart.orderType == OrderType.dineIn) ...[
                const SizedBox(height: 8),
                _field(fields.table, 'Table no. *', Icons.table_bar_rounded,
                    cart.setTableNumber),
              ],
              if (cart.orderType == OrderType.delivery) ...[
                const SizedBox(height: 8),
                _field(fields.delivery, 'Delivery address', Icons.place_outlined,
                    cart.setDeliveryAddress),
              ],
              const SizedBox(height: 14),
              if (hasItems)
                for (final line in cart.lines) _lineTile(context, line)
              else
                _emptyState(context),
              if (hasItems) ...[
                _extras(context),
                const SizedBox(height: 4),
                _paymentSection(context),
                const SizedBox(height: 10),
                _totalsRows(),
              ],
            ],
          ),
        ),
        _billingFooter(context, hasItems),
      ],
    );
  }

  // --- Header ---------------------------------------------------------------

  Widget _header(BuildContext context, bool hasItems) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 14, 8, 8),
      child: Row(
        children: [
          Container(
            width: 34,
            height: 34,
            decoration: BoxDecoration(
                color: BizBiteTheme.brandSoft,
                borderRadius: BorderRadius.circular(10)),
            child: const Icon(Icons.receipt_long_rounded,
                size: 19, color: BizBiteTheme.brand),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text('Current bill',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.w800)),
                Text(
                  hasItems ? '${cart.totalQuantity} item(s)' : 'Nothing added yet',
                  style: const TextStyle(fontSize: 12, color: AppColors.muted),
                ),
              ],
            ),
          ),
          if (hasItems && onHold != null)
            TextButton.icon(
              onPressed: onHold,
              icon: const Icon(Icons.pause_circle_outline_rounded, size: 18),
              label: const Text('Hold'),
              style: TextButton.styleFrom(
                  padding: const EdgeInsets.symmetric(horizontal: 10)),
            ),
          if (hasItems)
            TextButton(
              onPressed: () async {
                final ok = await showDialog<bool>(
                  context: context,
                  builder: (c) => AlertDialog(
                    title: const Text('Clear this bill?'),
                    actions: [
                      TextButton(
                          onPressed: () => Navigator.pop(c, false),
                          child: const Text('Keep')),
                      FilledButton(
                          onPressed: () => Navigator.pop(c, true),
                          child: const Text('Clear')),
                    ],
                  ),
                );
                if (ok == true) {
                  cart.clear();
                  fields.loadFrom(cart);
                }
              },
              style: TextButton.styleFrom(
                  foregroundColor: AppColors.error,
                  padding: const EdgeInsets.symmetric(horizontal: 10)),
              child: const Text('Clear'),
            ),
        ],
      ),
    );
  }

  Widget _orderTypeSelector() {
    return SizedBox(
      width: double.infinity,
      child: SegmentedButton<OrderType>(
        showSelectedIcon: false,
        segments: [
          for (final t in OrderType.values)
            ButtonSegment(value: t, label: Text(t.label, maxLines: 1)),
        ],
        selected: {cart.orderType},
        onSelectionChanged: (v) => cart.setOrderType(v.first),
      ),
    );
  }

  Widget _field(TextEditingController c, String label, IconData icon,
      ValueChanged<String> onChanged,
      {TextInputType? keyboard, bool caps = false}) {
    return TextField(
      controller: c,
      onChanged: onChanged,
      keyboardType: keyboard,
      textCapitalization: caps ? TextCapitalization.characters : TextCapitalization.words,
      decoration: InputDecoration(
        labelText: label,
        prefixIcon: Icon(icon, size: 20),
        isDense: true,
      ),
    );
  }

  Widget _customerRow() {
    final phone = cart.customerPhone.trim();
    final phoneBad = phone.isNotEmpty && India.normaliseMobile(phone) == null;
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Expanded(
          child: _field(fields.customer, 'Customer', Icons.person_outline_rounded,
              cart.setCustomerName),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: TextField(
            controller: fields.phone,
            onChanged: cart.setCustomerPhone,
            keyboardType: TextInputType.phone,
            maxLength: 14,
            decoration: InputDecoration(
              labelText: 'Mobile',
              counterText: '',
              isDense: true,
              prefixIcon: const Icon(Icons.phone_outlined, size: 20),
              errorText: phoneBad ? '10-digit mobile' : null,
            ),
          ),
        ),
      ],
    );
  }

  Widget _emptyState(BuildContext context) {
    return const Padding(
      padding: EdgeInsets.symmetric(vertical: 36),
      child: Column(
        children: [
          Icon(Icons.room_service_rounded, size: 44, color: AppColors.borderMuted),
          SizedBox(height: 10),
          Text('Tap items to start the bill',
              style: TextStyle(fontSize: 15, color: AppColors.muted)),
        ],
      ),
    );
  }

  Widget _lineTile(BuildContext context, CartLine line) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Column(
        children: [
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(line.foodItem.name,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w700)),
                    Text('${inr(line.foodItem.price)} each',
                        style: const TextStyle(fontSize: 12, color: AppColors.muted)),
                  ],
                ),
              ),
              _stepper(line),
              const SizedBox(width: 10),
              SizedBox(
                width: 76,
                child: Text(inr(line.lineTotal),
                    textAlign: TextAlign.end,
                    style: const TextStyle(fontSize: 14.5, fontWeight: FontWeight.w800)
                        .merge(BizBiteTheme.numeral)),
              ),
            ],
          ),
          _noteRow(context, line),
        ],
      ),
    );
  }

  Widget _stepper(CartLine line) {
    Widget btn(IconData icon, VoidCallback onTap, Color color, bool left) => InkWell(
          borderRadius: BorderRadius.horizontal(
              left: Radius.circular(left ? 18 : 0), right: Radius.circular(left ? 0 : 18)),
          onTap: onTap,
          child: SizedBox(width: 36, height: 36, child: Icon(icon, size: 18, color: color)),
        );
    return Container(
      height: 36,
      decoration: const ShapeDecoration(
          shape: StadiumBorder(side: BorderSide(color: BizBiteTheme.hairline)),
          color: Colors.white),
      child: Row(mainAxisSize: MainAxisSize.min, children: [
        btn(Icons.remove_rounded, () => cart.setQuantity(line.foodItem.id, line.quantity - 1),
            AppColors.muted, true),
        SizedBox(
          width: 26,
          child: Text('${line.quantity}',
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w800)
                  .merge(BizBiteTheme.numeral)),
        ),
        btn(Icons.add_rounded, () => cart.setQuantity(line.foodItem.id, line.quantity + 1),
            BizBiteTheme.brand, false),
      ]),
    );
  }

  Widget _noteRow(BuildContext context, CartLine line) {
    final hasNote = line.note.isNotEmpty;
    return InkWell(
      onTap: () => _editNote(context, line),
      borderRadius: BorderRadius.circular(8),
      child: Padding(
        padding: const EdgeInsets.only(top: 4),
        child: Row(children: [
          Icon(Icons.edit_note_rounded,
              size: 17, color: hasNote ? BizBiteTheme.brand : AppColors.faintMuted),
          const SizedBox(width: 5),
          Expanded(
            child: Text(hasNote ? line.note : 'Add kitchen note',
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                    fontSize: 12,
                    fontWeight: hasNote ? FontWeight.w600 : FontWeight.w500,
                    color: hasNote ? BizBiteTheme.brandDeep : AppColors.faintMuted)),
          ),
        ]),
      ),
    );
  }

  Future<void> _editNote(BuildContext context, CartLine line) async {
    final controller = TextEditingController(text: line.note);
    final note = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: Text('Note — ${line.foodItem.name}',
            style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w700)),
        content: TextField(
          controller: controller,
          autofocus: true,
          maxLines: 2,
          decoration: const InputDecoration(hintText: 'e.g. less spicy, no onion'),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('Cancel')),
          FilledButton(
              onPressed: () => Navigator.pop(context, controller.text),
              child: const Text('Save')),
        ],
      ),
    );
    controller.dispose();
    if (note != null) cart.setNote(line.foodItem.id, note);
  }

  /// Coupon, discount and bill note — collapsed to keep rush hours fast.
  Widget _extras(BuildContext context) {
    final valid = cart.campaignValid;
    final open = cart.campaignCode.isNotEmpty ||
        cart.discountAmount > 0 ||
        cart.notes.isNotEmpty;
    return Theme(
      data: Theme.of(context).copyWith(dividerColor: Colors.transparent),
      child: ExpansionTile(
        initiallyExpanded: open,
        tilePadding: EdgeInsets.zero,
        childrenPadding: const EdgeInsets.only(bottom: 8),
        title: const Text('Coupon, discount & note',
            style: TextStyle(fontSize: 13.5, fontWeight: FontWeight.w700, color: BizBiteTheme.brand)),
        children: [
          Row(crossAxisAlignment: CrossAxisAlignment.start, children: [
            Expanded(
              child: TextField(
                controller: fields.coupon,
                onChanged: cart.setCampaignCode,
                textCapitalization: TextCapitalization.characters,
                decoration: InputDecoration(
                  labelText: 'Coupon code',
                  isDense: true,
                  prefixIcon: const Icon(Icons.local_offer_outlined, size: 20),
                  errorText: valid == false ? 'Not valid for this bill' : null,
                  helperText: valid == true
                      ? '−${inr(cart.campaignDiscount)} applied'
                      : null,
                  helperStyle: const TextStyle(color: AppColors.success, fontWeight: FontWeight.w700),
                ),
              ),
            ),
            const SizedBox(width: 8),
            Expanded(
              child: _field(fields.discount, 'Discount ₹', Icons.currency_rupee_rounded,
                  (v) => cart.setDiscountAmount(double.tryParse(v.trim()) ?? 0),
                  keyboard: const TextInputType.numberWithOptions(decimal: true)),
            ),
          ]),
          const SizedBox(height: 8),
          _field(fields.notes, 'Bill / kitchen note', Icons.sticky_note_2_outlined, cart.setNotes),
        ],
      ),
    );
  }

  Widget _paymentSection(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Text('PAYMENT', style: _label),
        const SizedBox(height: 8),
        Row(
          children: [
            for (final mode in PaymentMode.values) ...[
              Expanded(
                child: _modeButton(mode),
              ),
              if (mode != PaymentMode.values.last) const SizedBox(width: 6),
            ],
          ],
        ),
        const SizedBox(height: 10),
        switch (cart.paymentMode) {
          PaymentMode.cash => _cashSection(),
          PaymentMode.upi => _upiSection(),
          PaymentMode.split => _splitSection(context),
          PaymentMode.credit => _hint(
              'Pay-later bill — customer name or mobile is required.',
              AppColors.warningBg,
              AppColors.warningDeep),
          PaymentMode.card => const SizedBox.shrink(),
        },
      ],
    );
  }

  Widget _modeButton(PaymentMode mode) {
    final selected = cart.paymentMode == mode;
    return Material(
      color: selected ? BizBiteTheme.brand : Colors.white,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(10),
        side: BorderSide(color: selected ? BizBiteTheme.brand : BizBiteTheme.hairline),
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(10),
        onTap: () => cart.setPaymentMode(mode),
        child: SizedBox(
          height: 40,
          child: Center(
            child: Text(mode.label,
                style: TextStyle(
                    fontSize: 12.5,
                    fontWeight: FontWeight.w700,
                    color: selected ? Colors.white : AppColors.slate600)),
          ),
        ),
      ),
    );
  }

  Widget _hint(String text, Color bg, Color fg) => Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
        decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(10)),
        child: Text(text, style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: fg)),
      );

  /// Cash received + quick-note buttons (exact, next ₹100, next ₹500) and
  /// the change to return.
  Widget _cashSection() {
    final p = cart.payable;
    final notes = <double>{
      p,
      (p / 100).ceil() * 100.0,
      (p / 500).ceil() * 500.0,
    }.where((n) => n > 0).toList()
      ..sort();
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Row(children: [
          Expanded(
            child: _field(fields.tendered, 'Cash received', Icons.payments_outlined,
                (v) => cart.setTendered(double.tryParse(v.trim()) ?? 0),
                keyboard: const TextInputType.numberWithOptions(decimal: true)),
          ),
          for (final n in notes) ...[
            const SizedBox(width: 6),
            ActionChip(
              label: Text('₹${n.toStringAsFixed(0)}'),
              onPressed: () {
                cart.setTendered(n);
                fields.tendered.text = n.toStringAsFixed(0);
              },
            ),
          ],
        ]),
        if (cart.changeDue > 0) ...[
          const SizedBox(height: 8),
          _hint('Return change  ${inr(cart.changeDue)}', AppColors.successBg, AppColors.successDeep),
        ],
      ],
    );
  }

  /// Dynamic UPI QR with the exact payable — customer scans with any app.
  Widget _upiSection() {
    final vpa = store?.upiVpa ?? '';
    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: BizBiteTheme.hairline),
      ),
      child: vpa.isEmpty || cart.payable <= 0
          ? Column(crossAxisAlignment: CrossAxisAlignment.stretch, children: [
              const Text(
                'Add your UPI ID in Profile → Manage shop to show a payment QR here.',
                style: TextStyle(fontSize: 12.5, color: AppColors.muted),
              ),
              const SizedBox(height: 8),
              _field(fields.upiRef, 'UPI ref / UTR (optional)', Icons.tag_rounded, cart.setUpiRef),
            ])
          : Row(children: [
              Container(
                color: Colors.white,
                child: QrImageView(
                  key: const ValueKey('upi-qr'),
                  data: India.upiLink(
                    vpa: vpa,
                    payeeName: store?.name ?? 'BizBite',
                    amount: cart.payable,
                    note: 'BizBite bill',
                  ),
                  size: 132,
                  padding: const EdgeInsets.all(4),
                  backgroundColor: Colors.white,
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Scan with any UPI app',
                        style: TextStyle(fontSize: 12, color: AppColors.muted)),
                    Text(inr(cart.payable),
                        style: const TextStyle(fontSize: 22, fontWeight: FontWeight.w800)
                            .merge(BizBiteTheme.numeral)),
                    Text(vpa,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(fontSize: 11.5, color: AppColors.muted)),
                    const SizedBox(height: 6),
                    TextField(
                      controller: fields.upiRef,
                      onChanged: cart.setUpiRef,
                      decoration: const InputDecoration(
                          hintText: 'UTR (optional)', isDense: true),
                    ),
                  ],
                ),
              ),
            ]),
    );
  }

  Widget _splitSection(BuildContext context) {
    final remaining = cart.splitRemaining;
    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.borderMuted),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          for (var i = 0; i < cart.splitLegs.length; i++)
            Row(children: [
              Text(cart.splitLegs[i]['mode']!.toUpperCase(),
                  style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13)),
              const Spacer(),
              Text(inr(double.tryParse(cart.splitLegs[i]['amount']!) ?? 0),
                  style: BizBiteTheme.numeral),
              IconButton(
                visualDensity: VisualDensity.compact,
                icon: const Icon(Icons.close_rounded, size: 18),
                onPressed: () => cart.removeSplitLeg(i),
              ),
            ]),
          Text(
            remaining == 0
                ? 'Fully covered ✓'
                : 'Remaining ${inr(remaining)} — tap a mode to add it',
            style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w700,
                color: remaining == 0 ? AppColors.success : AppColors.muted),
          ),
          if (remaining > 0) ...[
            const SizedBox(height: 6),
            Wrap(spacing: 6, children: [
              for (final mode in [PaymentMode.cash, PaymentMode.upi, PaymentMode.card, PaymentMode.credit])
                ActionChip(
                  label: Text('+ ${mode.label}'),
                  onPressed: () => _addSplit(context, mode, remaining),
                ),
            ]),
          ],
        ],
      ),
    );
  }

  Future<void> _addSplit(BuildContext context, PaymentMode mode, double remaining) async {
    final controller = TextEditingController(text: remaining.toStringAsFixed(2));
    final value = await showDialog<double>(
      context: context,
      builder: (c) => AlertDialog(
        title: Text('${mode.label} amount'),
        content: TextField(
          controller: controller,
          autofocus: true,
          keyboardType: const TextInputType.numberWithOptions(decimal: true),
          decoration: const InputDecoration(prefixText: '₹ '),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(c), child: const Text('Cancel')),
          FilledButton(
              onPressed: () => Navigator.pop(c, double.tryParse(controller.text.trim())),
              child: const Text('Add')),
        ],
      ),
    );
    controller.dispose();
    if (value != null && value > 0) cart.addSplitLeg(mode, value);
  }

  Widget _totalsRows() {
    Widget row(String label, String value, {Color? color, bool small = false}) => Padding(
          padding: const EdgeInsets.symmetric(vertical: 3),
          child: Row(children: [
            Expanded(
              child: Text(label,
                  style: TextStyle(
                      fontSize: small ? 12 : 14,
                      fontWeight: FontWeight.w600,
                      color: color ?? AppColors.muted)),
            ),
            Text(value,
                style: TextStyle(
                        fontSize: small ? 12.5 : 14.5,
                        fontWeight: FontWeight.w800,
                        color: color ?? AppColors.ink)
                    .merge(BizBiteTheme.numeral)),
          ]),
        );

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        row('Subtotal', inr(cart.subtotal)),
        if (cart.effectiveDiscount > 0)
          row('Discount', '−${inr(cart.effectiveDiscount)}', color: AppColors.warningDeep),
        if (cart.campaignDiscount > 0)
          row('Coupon ${cart.campaignCode}', '−${inr(cart.campaignDiscount)}',
              color: AppColors.warningDeep),
        if (cart.roundOff != 0)
          row('Round off', cart.roundOff.toStringAsFixed(2), small: true),
        if (store?.gstEnabled == true)
          const Text('Prices include GST', style: TextStyle(fontSize: 11.5, color: AppColors.faintMuted)),
        if (wallet != null && cart.payable > 0) _walletRow(cart.payable),
      ],
    );
  }

  Widget _walletRow(double payable) {
    final deduction = wallet!.deductionPreviewFor(payable);
    if (deduction <= 0) return const SizedBox.shrink();
    return Padding(
      padding: const EdgeInsets.only(top: 6),
      child: Row(children: [
        const Icon(Icons.account_balance_wallet_rounded, size: 14, color: BizBiteTheme.brand),
        const SizedBox(width: 6),
        Expanded(
          child: Text('Wallet: −${deduction.toStringAsFixed(2)} pts (1%)',
              style: const TextStyle(fontSize: 11.5, color: AppColors.muted)),
        ),
      ]),
    );
  }

  Widget _billingFooter(BuildContext context, bool hasItems) {
    return Container(
      decoration: const BoxDecoration(
        color: Colors.white,
        border: Border(top: BorderSide(color: BizBiteTheme.hairline)),
      ),
      padding: const EdgeInsets.fromLTRB(16, 10, 16, 12),
      child: SizedBox(
        width: double.infinity,
        height: 58,
        child: FilledButton(
          onPressed: settling || !hasItems ? null : onSettle,
          style: FilledButton.styleFrom(
            backgroundColor: BizBiteTheme.brand,
            disabledBackgroundColor: BizBiteTheme.hairline,
            padding: const EdgeInsets.symmetric(horizontal: 18),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          ),
          child: Row(children: [
            settling
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2.2, color: Colors.white))
                : const Icon(Icons.print_rounded, size: 20),
            const SizedBox(width: 8),
            Expanded(
              child: Text(settling ? 'Saving…' : 'Settle & print',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w800)),
            ),
            const SizedBox(width: 8),
            FittedBox(
              fit: BoxFit.scaleDown,
              child: Text(inr(cart.payable),
                  style: const TextStyle(fontSize: 21, fontWeight: FontWeight.w800)
                      .merge(BizBiteTheme.numeral)),
            ),
          ]),
        ),
      ),
    );
  }
}
