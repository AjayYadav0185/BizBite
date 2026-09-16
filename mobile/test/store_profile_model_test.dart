// Store profile ("About shop" / "Manage shop") wire-contract tests.
//
// Pure-Dart tests pinning the shape Laravel's `App\Support\StorePayload`
// returns from `POST /api/login`, `GET /api/menu` and `GET|PUT /api/store`:
// the Flutter model, the vault round-trip and the address/level getters the
// profile screen renders must all agree on those keys.
import 'package:flutter_test/flutter_test.dart';

import 'package:bizbite/features/auth/data/models/store_profile_model.dart';

void main() {
  test('parses the full store payload from GET /api/store', () {
    final store = StoreProfileModel.fromJson({
      'id': 7,
      'name': 'Apna Zaika',
      'phone': '9876543210',
      'alternate_phone': '9876500000',
      'address': '12 MG Road',
      'city': 'Indore',
      'state': 'MP',
      'pincode': '452001',
      'gstin': '23ABCDE1234F1Z5',
      'fssai_license': '1234567890123',
      'upi_vpa': 'apna@upi',
      'currency': 'INR',
      'default_gst_rate': '5.00',
      'is_gst_enabled': true,
      'print_header': 'APNA ZAIKA',
      'print_footer': 'Thank you, visit again!',
      'logo_path': 'store-logos/abc123.png',
      'logo_url': 'http://localhost/storage/store-logos/abc123.png',
    });

    expect(store.id, 7);
    expect(store.name, 'Apna Zaika');
    expect(store.alternatePhone, '9876500000');
    expect(store.pincode, '452001');
    expect(store.upiVpa, 'apna@upi');
    expect(store.defaultGstRate, 5.0);
    expect(store.isGstEnabled, isTrue);
    expect(store.printHeader, 'APNA ZAIKA');
    expect(store.printFooter, 'Thank you, visit again!');
    expect(store.hasLogo, isTrue);
    expect(store.initial, 'A');

    // "About shop" renders address → city → state → PIN in one line.
    expect(store.fullAddress, '12 MG Road, Indore, MP, 452001');
    // …and the compact store-hero line stays address + city only.
    expect(store.location, '12 MG Road, Indore');
  });

  test('minimal login/legacy payloads fall back safely', () {
    final store = StoreProfileModel.fromJson({
      'id': 3,
      'name': 'Demo Store',
      'upi_vpa': null,
    });

    expect(store.name, 'Demo Store');
    expect(store.phone, '');
    expect(store.alternatePhone, '');
    expect(store.gstin, '');
    expect(store.currency, 'INR');
    expect(store.defaultGstRate, 0);
    expect(store.isGstEnabled, isFalse);
    expect(store.hasLogo, isFalse);
    expect(store.fullAddress, '');
    expect(store.location, '');
  });

  test('is_gst_enabled accepts the boolean, string and int wire forms', () {
    bool flag(dynamic value) =>
        StoreProfileModel.fromJson({'id': 1, 'name': 'S', 'is_gst_enabled': value})
            .isGstEnabled;

    expect(flag(true), isTrue);
    expect(flag('1'), isTrue);
    expect(flag(1), isTrue);
    expect(flag(false), isFalse);
    expect(flag('0'), isFalse);
    expect(flag(null), isFalse);
  });

  test('toJson ↔ fromJson round-trips through the secured vault', () {
    const original = StoreProfileModel(
      id: 9,
      name: 'Cafe Nine',
      phone: '9000000000',
      alternatePhone: '9111111111',
      address: '9 Lake View',
      city: 'Bhopal',
      state: 'MP',
      pincode: '462001',
      gstin: '23AAAAA0000A1Z5',
      fssaiLicense: '9999999999999',
      upiVpa: 'cafe9@upi',
      currency: 'INR',
      defaultGstRate: 12.5,
      isGstEnabled: true,
      printHeader: 'CAFE NINE',
      printFooter: 'Visit again!',
      logoPath: 'store-logos/nine.png',
      logoUrl: 'https://bizbite.test/storage/store-logos/nine.png',
    );

    final restored = StoreProfileModel.fromJson(original.toJson());

    expect(restored, original);
    expect(restored.logoUrl, original.logoUrl);
    expect(restored.printFooter, 'Visit again!');
  });

  test('monogram falls back to the BizBite mark when the name is blank', () {
    const blank = StoreProfileModel(id: 1, name: '   ');
    expect(blank.initial, 'B');
    expect(blank.hasLogo, isFalse);
  });
}