import 'package:flutter/foundation.dart';

import 'data/models/order_receipt_model.dart';

/// Micro-controller tracking the one pending receipt of a settlement flow.
///
/// The POS screen sets [pendingReceipt] after a successful `POST /api/orders`;
/// the root navigator swaps to the receipt screen while it is non-null, and
/// "New bill" clears it to return to the billing grid.
class OrderFlowController with ChangeNotifier implements Listenable {
  OrderReceiptModel? pendingReceipt;

  void showReceipt(OrderReceiptModel receipt) {
    pendingReceipt = receipt;
    notifyListeners();
  }

  void clearReceipt() {
    if (pendingReceipt == null) return;
    pendingReceipt = null;
    notifyListeners();
  }
}