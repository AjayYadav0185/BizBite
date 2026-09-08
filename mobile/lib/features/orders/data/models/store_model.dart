import 'package:equatable/equatable.dart';

import '../../../../core/utils/parse_utils.dart';

/// Store branding block embedded in every successful order response:
///
/// ```
/// "store": {
///   "name": "Demo Store", "phone": "555-0100", "address": "123 Main Street",
///   "print_header": "BIZBITE POS", "print_footer": "Thank you for your order!"
/// }
/// ```
///
/// Printed verbatim on the thermal receipt (Step 4 ESC/POS pipeline).
class StoreModel extends Equatable {
  const StoreModel({
    required this.name,
    this.phone = '',
    this.address = '',
    this.printHeader = '',
    this.printFooter = '',
  });

  final String name;
  final String phone;
  final String address;
  final String printHeader;
  final String printFooter;

  factory StoreModel.fromJson(Map<String, dynamic> json) => StoreModel(
        name: toNullableString(json['name']),
        phone: toNullableString(json['phone']),
        address: toNullableString(json['address']),
        printHeader: toNullableString(json['print_header']),
        printFooter: toNullableString(json['print_footer']),
      );

  Map<String, dynamic> toJson() => {
        'name': name,
        'phone': phone,
        'address': address,
        'print_header': printHeader,
        'print_footer': printFooter,
      };

  @override
  List<Object?> get props => [name, phone, address, printHeader, printFooter];
}
