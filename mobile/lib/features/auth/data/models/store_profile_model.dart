import 'package:equatable/equatable.dart';

import '../../../../core/utils/parse_utils.dart';

/// Store branding/profile payload, shared by `POST /api/login` and
/// `GET /api/menu`:
///
/// `POST /api/login`   -> { "id", "name", "upi_vpa", "currency" }
/// `GET  /api/menu`    -> { "id", "name", "phone", "address", "city",
///                          "state", "pincode", "gstin", "fssai_license",
///                          "upi_vpa", "currency" }
///
/// All fields are parsed defensively so a payload from either endpoint maps
/// cleanly onto this model.
class StoreProfileModel extends Equatable {
  const StoreProfileModel({
    required this.id,
    required this.name,
    this.phone = '',
    this.address = '',
    this.city = '',
    this.state = '',
    this.pincode = '',
    this.gstin = '',
    this.fssaiLicense = '',
    this.upiVpa = '',
    this.currency = 'INR',
  });

  final int id;
  final String name;
  final String phone;
  final String address;
  final String city;
  final String state;
  final String pincode;
  final String gstin;
  final String fssaiLicense;
  final String upiVpa;
  final String currency;

  String get location => [
        if (address.isNotEmpty) address,
        if (city.isNotEmpty && city != address) city,
      ].join(', ');

  factory StoreProfileModel.fromJson(Map<String, dynamic> json) =>
      StoreProfileModel(
        id: toInt(json['id']),
        name: toNullableString(json['name']),
        phone: toNullableString(json['phone']),
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
      );

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'phone': phone,
        'address': address,
        'city': city,
        'state': state,
        'pincode': pincode,
        'gstin': gstin,
        'fssai_license': fssaiLicense,
        'upi_vpa': upiVpa,
        'currency': currency,
      };

  @override
  List<Object?> get props =>
      [id, name, phone, address, city, state, pincode, gstin, fssaiLicense, upiVpa, currency];
}