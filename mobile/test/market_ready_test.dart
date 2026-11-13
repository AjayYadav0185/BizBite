import 'dart:convert';

import 'package:bizbite/core/utils/india.dart';
import 'package:bizbite/features/menu/data/models/food_item_model.dart';
import 'package:bizbite/features/ops/data/models/console_models.dart';
import 'package:bizbite/features/orders/cart_controller.dart';
import 'package:bizbite/features/orders/data/models/order_models.dart';
import 'package:bizbite/features/orders/data/models/order_receipt_model.dart';
import 'package:bizbite/features/orders/data/models/store_model.dart';
import 'package:bizbite/features/orders/receipt_document.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';

FoodItemModel item(int id, double price, {String name = 'Item', int gst = 5}) =>
    FoodItemModel(id: id, categoryId: 1, name: '$name $id', price: price, gstRate: gst);

CampaignModel coupon(String code, double value,
        {CampaignType type = CampaignType.percent, double minOrder = 0, bool active = true}) =>
    CampaignModel(
        id: 1, name: code, code: code, type: type, value: value,
        minOrderAmount: minOrder, isActive: active);

Map<String, dynamic> serverReceipt({bool gst = true}) => {
      'order_id': 42,
      'order_number': '001-20261114-0042',
      'total_amount': '200.00',
      'payment_mode': 'cash',
      'status': 'completed',
      'order_type': 'dine_in',
      'table_number': 'T4',
      'placed_at': '14 Nov 2026, 01:15 PM',
      'cashier': 'Priya',
      'total_quantity': 3,
      'subtotal': '223.00',
      'discount_amount': '23.00',
      'tax_amount': '20.63',
      'round_off': '0.00',
      'tendered_amount': '500.00',
      'change_amount': '300.00',
      'amount_in_words': 'Rupees Two Hundred Only',
      'tax_breakup': [
        {'rate': 5, 'taxable': '90.00', 'cgst': '2.25', 'sgst': '2.25'},
        {'rate': 18, 'taxable': '90.00', 'cgst': '8.06', 'sgst': '8.07'},
      ],
      'store': {
        'name': 'Apna Dhaba',
        'address': 'Shop 12, Sector 18',
        'city': 'Noida',
        'pincode': '201301',
        'gstin': '09ABCDE1234F1Z5',
        'fssai': '10012043001234',
        'gst_enabled': gst,
        'print_footer': 'Shukriya!',
      },
      'items': [
        {'food_item_name': 'Paneer Butter Masala Extra Large Family Pack', 'quantity': 1, 'price': '105.00', 'subtotal': '105.00', 'gst_rate': 5},
        {'food_item_name': 'Mojito', 'quantity': 2, 'price': '59.00', 'subtotal': '118.00', 'gst_rate': 18},
      ],
    };

void main() {
  setUp(() => SharedPreferences.setMockInitialValues({}));

  group('India helpers (twin of App\\Support\\India)', () {
    test('lakh grouping and amount in words', () {
      expect(India.number(1234567.5), '12,34,567.50');
      expect(India.number(999), '999.00');
      expect(India.number(10000000, decimals: 0), '1,00,00,000');
      expect(India.words(1210.5), 'Rupees One Thousand Two Hundred Ten and Fifty Paise Only');
      expect(India.words(10500000), 'Rupees One Crore Five Lakh Only');
      expect(India.words(0), 'Rupees Zero Only');
    });

    test('GSTIN check digit, state, mobile, UPI link', () {
      expect(India.isValidGstin('27AAPFU0939F1ZV'), isTrue);
      expect(India.isValidGstin('07abcde1234f1z2'), isTrue);
      expect(India.isValidGstin('27AAPFU0939F1ZA'), isFalse);
      expect(India.isValidGstin('99AAPFU0939F1ZV'), isFalse);
      expect(India.stateFromGstin('06ABCDE1234F1Z4'), 'Haryana');
      expect(India.normaliseMobile('+91 98110-45678'), '9811045678');
      expect(India.normaliseMobile('12345'), isNull);
      final link = India.upiLink(vpa: 'shop@okaxis', payeeName: 'Apna Dhaba', amount: 345);
      expect(link, startsWith('upi://pay?pa=shop@okaxis&'));
      expect(link, contains('am=345.00'));
      expect(India.whatsappUri('hi', mobile: '9811045678').toString(),
          startsWith('https://wa.me/919811045678?text='));
    });
  });

  group('Cart totals match the server', () {
    test('coupon preview + nearest-rupee round-off (199.00 −10% = 179.10 → 179)', () {
      final cart = CartController()..setCampaigns([coupon('NCR10', 10)]);
      cart.add(item(1, 99.5), quantity: 2);
      cart.setCampaignCode('ncr10');
      expect(cart.campaignDiscount, 19.9);
      expect(cart.campaignValid, isTrue);
      expect(cart.payable, 179.0);
      expect(cart.roundOff, -0.1);
    });

    test('coupon below minimum order or inactive is flagged, not applied', () {
      final cart = CartController()
        ..setCampaigns([coupon('BIG50', 50, type: CampaignType.flat, minOrder: 400), coupon('OLD', 10, active: false)]);
      cart.add(item(1, 100));
      cart.setCampaignCode('BIG50');
      expect(cart.campaignValid, isFalse);
      cart.setCampaignCode('OLD');
      expect(cart.campaignDiscount, 0);
      cart.setCampaignCode('NOPE');
      expect(cart.campaignValid, isFalse);
      expect(cart.payable, 100);
    });

    test('manual discount + coupon never exceed the subtotal', () {
      final cart = CartController()..setCampaigns([coupon('FLAT', 80, type: CampaignType.flat)]);
      cart.add(item(1, 100));
      cart.setDiscountAmount(50);
      cart.setCampaignCode('FLAT');
      expect(cart.campaignDiscount, 50);
      expect(cart.payable, 0);
    });

    test('change due and split remaining', () {
      final cart = CartController();
      cart.add(item(1, 245));
      cart.setTendered(500);
      expect(cart.changeDue, 255);
      cart.setPaymentMode(PaymentMode.split);
      cart.addSplitLeg(PaymentMode.cash, 100);
      expect(cart.splitRemaining, 145);
      expect(cart.validate(), contains('short'));
      cart.addSplitLeg(PaymentMode.upi, 145);
      expect(cart.validate(), isNull);
    });
  });

  group('Counter checks (same as web POS)', () {
    test('credit needs a customer, dine-in needs a table, mobile must be valid', () {
      final cart = CartController()..add(item(1, 50));
      cart.setPaymentMode(PaymentMode.credit);
      expect(cart.validate(), contains('Credit'));
      cart.setCustomerName('Sharma ji');
      expect(cart.validate(), isNull);

      cart.setOrderType(OrderType.dineIn);
      expect(cart.validate(), contains('table'));
      cart.setTableNumber('T5');
      expect(cart.validate(), isNull);

      cart.setCustomerPhone('12345');
      expect(cart.validate(), contains('mobile'));
    });

    test('per-item kitchen notes travel in the bill note (max 200)', () {
      final cart = CartController()..add(item(7, 90, name: 'Paneer'));
      cart.setNote(7, 'less spicy');
      cart.setNotes('Pack separately');
      expect(cart.combinedNotes, 'Pack separately; Paneer 7: less spicy');
    });
  });

  group('Hold / resume', () {
    test('round-trips the bill and survives a restart', () async {
      final cart = CartController();
      cart.add(item(1, 80), quantity: 2);
      cart.setOrderType(OrderType.dineIn);
      cart.setTableNumber('T5');
      expect(cart.hold(), isNull);
      expect(cart.isEmpty, isTrue);
      expect(cart.held.single.label, 'Table T5');
      await Future<void>.delayed(Duration.zero);

      final fresh = CartController();
      await Future<void>.delayed(const Duration(milliseconds: 10));
      expect(fresh.held, hasLength(1));

      fresh.resume(fresh.held.single.id);
      expect(fresh.tableNumber, 'T5');
      expect(fresh.orderType, OrderType.dineIn);
      expect(fresh.quantityOf(1), 2);
      expect(fresh.held, isEmpty);
    });

    test('resuming parks the bill that was on screen', () {
      final cart = CartController();
      cart.add(item(1, 10));
      cart.hold();
      cart.add(item(2, 20));
      cart.resume(cart.held.single.id);
      expect(cart.quantityOf(1), 1);
      expect(cart.held, hasLength(1));
    });

    test('empty cart cannot be held', () {
      expect(CartController().hold(), contains('empty'));
    });
  });

  group('Receipt model + document', () {
    test('parses GST fields and dine_in order type from the server', () {
      final r = OrderReceiptModel.fromJson(serverReceipt());
      expect(r.orderType, OrderType.dineIn);
      expect(r.taxBreakup, hasLength(2));
      expect(r.taxBreakup.last.tax, 16.13);
      expect(r.store.printsTaxInvoice, isTrue);
      expect(r.items.last.gstRate, 18);
      final round = OrderReceiptModel.fromJson(r.toJson());
      expect(round, r);
    });

    for (final columns in [32, 48]) {
      test('$columns-col tax invoice fits the roll and carries GST lines', () {
        final r = OrderReceiptModel.fromJson(serverReceipt());
        final lines = ReceiptDocument(columns: columns).bill(r);
        for (final line in lines) {
          final width = line.large ? columns ~/ 2 : columns;
          expect(line.text.length, lessThanOrEqualTo(width), reason: '"${line.text}"');
        }
        final text = lines.map((l) => l.text).join('\n');
        expect(text, contains('TAX INVOICE'));
        expect(text, contains('GSTIN: 09ABCDE1234F1Z5'));
        expect(text, contains('FSSAI Lic. No: 10012043001234'));
        expect(text, contains('CGST'));
        expect(text, contains('SAC 996331'));
        expect(text, contains('Dine-in - Table T4'));
        expect(text, contains('Rupees Two Hundred Only'));
        expect(text, contains('Change'));
        expect(text.replaceAll('\n', ' '), contains('Noida - 201301'));
      });
    }

    test('unregistered store prints Bill of Supply without tax lines', () {
      final r = OrderReceiptModel.fromJson(serverReceipt(gst: false));
      final text = ReceiptDocument().bill(r).map((l) => l.text).join('\n');
      expect(text, contains('BILL OF SUPPLY'));
      expect(text, isNot(contains('CGST')));
      expect(text, isNot(contains('GSTIN')));
    });

    test('KOT has quantities and table but no prices', () {
      final r = OrderReceiptModel.fromJson(serverReceipt());
      final text = ReceiptDocument().kot(r).map((l) => l.text).join('\n');
      expect(text, contains('KOT'));
      expect(text, contains('TABLE T4'));
      expect(text, contains('2 x Mojito'));
      expect(text, isNot(contains('59.00')));
      expect(text, isNot(contains('Rs.')));
    });

    test('reprint is marked as a duplicate copy; WhatsApp text has the total', () {
      final r = OrderReceiptModel.fromJson(serverReceipt()).asReprint();
      expect(ReceiptDocument().bill(r).map((l) => l.text), contains(contains('DUPLICATE COPY')));
      expect(ReceiptDocument.whatsappText(r), contains('*Total: ₹200.00*'));
    });

    test('store address does not repeat city / PIN already in it', () {
      const s = StoreModel(name: 'X', address: 'Main Market, Noida 201301', city: 'Noida', pincode: '201301');
      expect(s.fullAddress, 'Main Market, Noida 201301');
    });
  });

  test('order request JSON stays valid with quotes, backslashes, newlines and split legs', () {
    const request = PlaceOrderRequest(
      paymentMode: PaymentMode.split,
      items: [OrderLineRequest(id: 1, quantity: 2)],
      notes: 'He said "no onion"\nC:\\path',
      splitLegs: [
        {'mode': 'cash', 'amount': '100.00'},
        {'mode': 'upi', 'amount': '45.00'},
      ],
      idempotencyKey: 'k-1',
    );
    final decoded = jsonDecode(jsonEncode(request.toJson())) as Map<String, dynamic>;
    expect(decoded['notes'], 'He said "no onion"\nC:\\path');
    expect((decoded['split_details'] as List).first, {'mode': 'cash', 'amount': '100.00'});
  });
}
