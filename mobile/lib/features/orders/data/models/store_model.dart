import 'package:equatable/equatable.dart';

import '../../../../core/utils/parse_utils.dart';

/// Store block embedded in every order receipt (`OrderReceipt::toArray()`
/// → `store`). Carries everything an Indian restaurant bill must print:
/// trade name, address, GSTIN + FSSAI, and the UPI ID for the payment QR.
class StoreModel extends Equatable {
  const StoreModel({
    required this.name,
    this.phone = '',
    this.address = '',
    this.city = '',
    this.state = '',
    this.pincode = '',
    this.printHeader = '',
    this.printFooter = '',
    this.gstin = '',
    this.fssai = '',
    this.gstEnabled = false,
    this.upiVpa = '',
  });

  final String name;
  final String phone;
  final String address;
  final String city;
  final String state;
  final String pincode;
  final String printHeader;
  final String printFooter;
  final String gstin;
  final String fssai;
  final bool gstEnabled;
  final String upiVpa;

  /// GST registered with a GSTIN → print a TAX INVOICE (else Bill of Supply).
  bool get printsTaxInvoice => gstEnabled && gstin.isNotEmpty;

  /// Address line with city / PIN appended only when not already in it.
  String get fullAddress {
    var out = address;
    if (city.isNotEmpty && !out.contains(city)) {
      out = out.isEmpty ? city : '$out, $city';
    }
    if (pincode.isNotEmpty && !out.contains(pincode)) {
      out = out.isEmpty ? pincode : '$out - $pincode';
    }
    return out;
  }

  factory StoreModel.fromJson(Map<String, dynamic> json) => StoreModel(
        name: toNullableString(json['name']),
        phone: toNullableString(json['phone']),
        address: toNullableString(json['address']),
        city: toNullableString(json['city']),
        state: toNullableString(json['state']),
        pincode: toNullableString(json['pincode']),
        printHeader: toNullableString(json['print_header']),
        printFooter: toNullableString(json['print_footer']),
        gstin: toNullableString(json['gstin']),
        fssai: toNullableString(json['fssai'] ?? json['fssai_license']),
        gstEnabled: json['gst_enabled'] == true ||
            json['gst_enabled'] == 1 ||
            json['is_gst_enabled'] == true ||
            json['is_gst_enabled'] == 1,
        upiVpa: toNullableString(json['upi_vpa']),
      );

  Map<String, dynamic> toJson() => {
        'name': name,
        'phone': phone,
        'address': address,
        'city': city,
        'state': state,
        'pincode': pincode,
        'print_header': printHeader,
        'print_footer': printFooter,
        'gstin': gstin,
        'fssai': fssai,
        'gst_enabled': gstEnabled,
        'upi_vpa': upiVpa,
      };

  @override
  List<Object?> get props => [
        name, phone, address, city, state, pincode, printHeader, printFooter,
        gstin, fssai, gstEnabled, upiVpa,
      ];
}
