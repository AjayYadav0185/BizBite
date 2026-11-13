import 'package:equatable/equatable.dart';

import '../../../../core/utils/india.dart';
import '../../../../core/utils/parse_utils.dart';
import 'order_models.dart';
import 'store_model.dart';

/// One GST slab row on a tax invoice (`tax_breakup[]` from the server).
class TaxRow extends Equatable {
  const TaxRow({
    required this.rate,
    required this.taxable,
    required this.cgst,
    required this.sgst,
  });

  final int rate;
  final double taxable;
  final double cgst;
  final double sgst;

  double get tax => India.paise(cgst + sgst);

  factory TaxRow.fromJson(Map<String, dynamic> json) => TaxRow(
        rate: toInt(json['rate']),
        taxable: toDouble(json['taxable']),
        cgst: toDouble(json['cgst']),
        sgst: toDouble(json['sgst']),
      );

  Map<String, dynamic> toJson() => {
        'rate': rate,
        'taxable': taxable.toStringAsFixed(2),
        'cgst': cgst.toStringAsFixed(2),
        'sgst': sgst.toStringAsFixed(2),
        'tax': tax.toStringAsFixed(2),
      };

  @override
  List<Object?> get props => [rate, taxable, cgst, sgst];
}

/// The receipt payload returned by `POST /api/orders` (201) and
/// `GET /api/orders/{id}/receipt` (reprint).
///
/// Mirrors `App\Services\OrderReceipt::toArray()` on the Laravel side,
/// including the GST tax-invoice fields (`tax_breakup`, `amount_in_words`,
/// `store.gstin` / `store.fssai`). Rendered by [ReceiptDocument] for both the
/// on-screen preview and the Bluetooth ESC/POS print.
class OrderReceiptModel extends Equatable {
  const OrderReceiptModel({
    required this.orderId,
    required this.orderNumber,
    required this.totalAmount,
    required this.paymentMode,
    required this.status,
    required this.store,
    required this.cashier,
    required this.placedAt,
    required this.totalQuantity,
    required this.items,
    this.orderType = OrderType.takeaway,
    this.customerName = '',
    this.customerPhone = '',
    this.upiRef = '',
    this.isOffline = false,
    this.isReprint = false,
    this.walletDeduction = '0.00',
    this.walletBalanceAfter,
    this.subtotal,
    this.discountAmount = 0,
    this.taxAmount = 0,
    this.roundOff = 0,
    this.taxBreakup = const [],
    this.amountInWords = '',
    this.sacCode = India.sacRestaurant,
    this.tableNumber = '',
    this.notes = '',
    this.campaignCode = '',
    this.tenderedAmount = 0,
    this.changeAmount = 0,
    this.deliveryAddress = '',
  });

  final int orderId;
  final String orderNumber;
  final double totalAmount;
  final PaymentMode paymentMode;

  /// Backend guarantees 'completed'; kept as string for forward safety.
  final String status;

  final StoreModel store;
  final String cashier;
  final String placedAt;
  final int totalQuantity;
  final List<OrderItemModel> items;

  final OrderType orderType;
  final String customerName;
  final String customerPhone;
  final String upiRef;

  /// True when this receipt was built locally from the cart while offline
  /// (LOCAL-XXXX, `status: pending_sync`). Never serialized to the server.
  final bool isOffline;

  /// True when reprinted from the order history ("DUPLICATE COPY").
  final bool isReprint;

  /// Wallet points debited for this bill (1% of the settled total).
  final String walletDeduction;
  final String? walletBalanceAfter;

  /// Gross item total before discount (null on very old server payloads).
  final double? subtotal;
  final double discountAmount;
  final double taxAmount;
  final double roundOff;
  final List<TaxRow> taxBreakup;
  final String amountInWords;
  final String sacCode;
  final String tableNumber;
  final String notes;
  final String campaignCode;
  final double tenderedAmount;
  final double changeAmount;
  final String deliveryAddress;

  double get grossTotal =>
      subtotal ?? items.fold(0.0, (sum, item) => sum + item.subtotal);

  String get words =>
      amountInWords.isNotEmpty ? amountInWords : India.words(totalAmount);

  OrderReceiptModel asReprint() => OrderReceiptModel.fromJson(toJson(), reprint: true);

  factory OrderReceiptModel.fromJson(Map<String, dynamic> json,
      {bool reprint = false}) {
    return OrderReceiptModel(
      orderId: toInt(json['order_id']),
      orderNumber: toNullableString(json['order_number']),
      totalAmount: toDouble(json['total_amount']),
      paymentMode: paymentModeFromJson(json['payment_mode']?.toString()),
      status: toNullableString(json['status']),
      store: StoreModel.fromJson(toMap(json['store'])),
      cashier: toNullableString(json['cashier']),
      placedAt: toNullableString(json['placed_at']),
      totalQuantity: toInt(json['total_quantity']),
      items: toListOfMaps(json['items'])
          .map(OrderItemModel.fromJson)
          .toList(growable: false),
      orderType: orderTypeFromJson(json['order_type']?.toString()),
      customerName: toNullableString(json['customer_name']),
      customerPhone: toNullableString(json['customer_phone']),
      upiRef: toNullableString(json['upi_ref']),
      isReprint: reprint || json['reprint'] == true,
      walletDeduction:
          toNullableString(json['wallet_deduction'], fallback: '0.00'),
      walletBalanceAfter: (json['wallet_balance_after'] == null)
          ? null
          : toNullableString(json['wallet_balance_after']),
      subtotal: json['subtotal'] == null ? null : toDouble(json['subtotal']),
      discountAmount: toDouble(json['discount_amount']),
      taxAmount: toDouble(json['tax_amount']),
      roundOff: toDouble(json['round_off']),
      taxBreakup: toListOfMaps(json['tax_breakup'])
          .map(TaxRow.fromJson)
          .toList(growable: false),
      amountInWords: toNullableString(json['amount_in_words']),
      sacCode: toNullableString(json['sac_code'],
          fallback: India.sacRestaurant),
      tableNumber: toNullableString(json['table_number']),
      notes: toNullableString(json['notes']),
      campaignCode: toNullableString(json['campaign_code']),
      tenderedAmount: toDouble(json['tendered_amount']),
      changeAmount: toDouble(json['change_amount']),
      deliveryAddress: toNullableString(json['delivery_address']),
    );
  }

  Map<String, dynamic> toJson() => {
        'order_id': orderId,
        'order_number': orderNumber,
        'total_amount': totalAmount.toStringAsFixed(2),
        'payment_mode': paymentMode.wireValue,
        'status': status,
        'store': store.toJson(),
        'cashier': cashier,
        'placed_at': placedAt,
        'total_quantity': totalQuantity,
        'items': items.map((item) => item.toJson()).toList(),
        'order_type': orderType.wireValue,
        'customer_name': customerName,
        'customer_phone': customerPhone,
        'upi_ref': upiRef,
        'reprint': isReprint,
        'wallet_deduction': walletDeduction,
        'wallet_balance_after': walletBalanceAfter,
        if (subtotal != null) 'subtotal': subtotal!.toStringAsFixed(2),
        'discount_amount': discountAmount.toStringAsFixed(2),
        'tax_amount': taxAmount.toStringAsFixed(2),
        'round_off': roundOff.toStringAsFixed(2),
        'tax_breakup': taxBreakup.map((row) => row.toJson()).toList(),
        'amount_in_words': amountInWords,
        'sac_code': sacCode,
        'table_number': tableNumber,
        'notes': notes,
        'campaign_code': campaignCode,
        'tendered_amount': tenderedAmount.toStringAsFixed(2),
        'change_amount': changeAmount.toStringAsFixed(2),
        'delivery_address': deliveryAddress,
      };

  @override
  List<Object?> get props => [
        orderId,
        orderNumber,
        totalAmount,
        paymentMode,
        status,
        store,
        cashier,
        placedAt,
        totalQuantity,
        items,
        orderType,
        customerName,
        customerPhone,
        upiRef,
        isOffline,
        isReprint,
        walletDeduction,
        walletBalanceAfter,
        subtotal,
        discountAmount,
        taxAmount,
        roundOff,
        taxBreakup,
        amountInWords,
        sacCode,
        tableNumber,
        notes,
        campaignCode,
        tenderedAmount,
        changeAmount,
        deliveryAddress,
      ];
}
