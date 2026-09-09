import 'package:equatable/equatable.dart';

import '../../../../core/utils/parse_utils.dart';
import 'store_profile_model.dart';
import 'user_model.dart';

/// Mirror of `App\Http\Controllers\Api\AuthController@login`:
///
/// ```
/// {
///   "token": "12|abcdef...",
///   "token_type": "Bearer",
///   "user": { "id":1, "name":"Rajesh Sharma", "email":"admin@mail.com",
///             "phone":"...", "role":"admin", "store_id":1 },
///   "store": { "id":1, "name":"Apna Zaika...", "upi_vpa":"...", "currency":"INR" }
/// }
/// ```
///
/// The received Sanctum token is persisted by [SessionController]
/// (via [TokenStore]) so later requests authenticate through the AuthInterceptor.
class AuthResponseModel extends Equatable {
  const AuthResponseModel({
    required this.plainTextToken,
    required this.user,
    this.store,
  });

  final String plainTextToken;
  final UserModel user;
  final StoreProfileModel? store;

  factory AuthResponseModel.fromJson(Map<String, dynamic> json) {
    return AuthResponseModel(
      plainTextToken: toNullableString(json['token']),
      user: UserModel.fromJson(toMap(json['user'])),
      store: json['store'] == null
          ? null
          : StoreProfileModel.fromJson(toMap(json['store'])),
    );
  }

  Map<String, dynamic> toJson() => {
        'token': plainTextToken,
        'token_type': 'Bearer',
        'user': user.toJson(),
        if (store != null) 'store': store!.toJson(),
      };

  @override
  List<Object?> get props => [plainTextToken, user, store];
}