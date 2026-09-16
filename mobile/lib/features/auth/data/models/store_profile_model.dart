import 'package:equatable/equatable.dart';

import '../../../../core/utils/parse_utils.dart';

/// Store branding/profile payload, shared by `POST /api/login`,
/// `GET /api/menu` and `GET|PUT /api/store`:
///
/// `App\Support\StorePayload` on the Laravel side serializes ONE shape for
/// every one of those endpoints, so this model parses the same keys no matter
/// which screen fetched it ("About shop" card, receipt templates, POS hero):
///
/// ```
/// { "id", "name", "phone", "alternate_phone", "address", "city", "state",
///   "pincode", "gstin", "fssai_license", "upi_vpa", "currency",
///   "default_gst_rate", "is_gst_enabled", "print_header", "print_footer",
///   "logo_path", "logo_url" }
/// ```
///
/// All fields are parsed defensively so a payload from any endpoint (or an
/// older server revision) maps cleanly onto this model.
class StoreProfileModel extends Equatable {
  const StoreProfileModel({
    required this.id,
    required this.name,
    this.phone = '',
    this.alternatePhone = '',
    this.address = '',
    this.city = '',
    this.state = '',
    this.pincode = '',
    this.gstin = '',
    this.fssaiLicense = '',
    this.upiVpa = '',
    this.currency = 'INR',
    this.defaultGstRate = 0,
    this.isGstEnabled = false,
    this.printHeader = '',
    this.printFooter = '',
    this.logoPath = '',
    this.logoUrl = '',
  });

  final int id;
  final String name;
  final String phone;
  final String alternatePhone;
  final String address;
  final String city;
  final String state;
  final String pincode;
  final String gstin;
  final String fssaiLicense;
  final String upiVpa;
  final String currency;

  /// Store-level GST defaults ("Tax defaults" in Manage shop). Per-item GST
  /// rates on the menu still govern what a bill actually charges.
  final double defaultGstRate;
  final bool isGstEnabled;

  /// Owner-customized thermal receipt template lines (web Receipt Customizer).
  final String printHeader;
  final String printFooter;

  /// Relative path on the `public` disk (`.env`/server detail) …
  final String logoPath;

  /// … and the absolute, displayable URL of the uploaded shop logo.
  final String logoUrl;

  bool get hasLogo => logoUrl.isNotEmpty;

  /// Single monogram letter for the logo fallback avatar.
  String get initial {
    final trimmed = name.trim();
    return trimmed.isEmpty ? 'B' : trimmed[0].toUpperCase();
  }

  /// Short one-line location used by the store hero card.
  String get location => [
        if (address.isNotEmpty) address,
        if (city.isNotEmpty && city != address) city,
      ].join(', ');

  /// Full postal address including state + PIN, for the "About shop" block.
  String get fullAddress => [
        if (address.isNotEmpty) address,
        if (city.isNotEmpty) city,
        if (state.isNotEmpty) state,
        if (pincode.isNotEmpty) pincode,
      ].join(', ');

  factory StoreProfileModel.fromJson(Map<String, dynamic> json) =>
      StoreProfileModel(
        id: toInt(json['id']),
        name: toNullableString(json['name']),
        phone: toNullableString(json['phone']),
        alternatePhone: toNullableString(json['alternate_phone']),
        address: toNullableString(json['address']),
        city: toNullableString(json['city']),
        state: toNullableString(json['state']),
        pincode: toNullableString(json['pincode']),
        gstin: toNullableString(json['gstin']),
        fssaiLicense: toNullableString(json['fssai_license']),
        upiVpa: toNullableString(json['upi_vpa']),
        currency: toNullableString(json['currency']).isNotEmpty
            ? toNullableString(json['currency'])
            : 'INR',
        defaultGstRate: toDouble(json['default_gst_rate']),
        isGstEnabled: json['is_gst_enabled'] == true ||
            toNullableString(json['is_gst_enabled']) == '1',
        printHeader: toNullableString(json['print_header']),
        printFooter: toNullableString(json['print_footer']),
        logoPath: toNullableString(json['logo_path']),
        logoUrl: toNullableString(json['logo_url']),
      );

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'phone': phone,
        'alternate_phone': alternatePhone,
        'address': address,
        'city': city,
        'state': state,
        'pincode': pincode,
        'gstin': gstin,
        'fssai_license': fssaiLicense,
        'upi_vpa': upiVpa,
        'currency': currency,
        'default_gst_rate': defaultGstRate,
        'is_gst_enabled': isGstEnabled,
        'print_header': printHeader,
        'print_footer': printFooter,
        'logo_path': logoPath,
        'logo_url': logoUrl,
      };

  @override
  List<Object?> get props => [
        id,
        name,
        phone,
        alternatePhone,
        address,
        city,
        state,
        pincode,
        gstin,
        fssaiLicense,
        upiVpa,
        currency,
        defaultGstRate,
        isGstEnabled,
        printHeader,
        printFooter,
        logoPath,
        logoUrl,
      ];
}