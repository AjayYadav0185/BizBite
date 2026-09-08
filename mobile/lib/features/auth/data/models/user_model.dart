import 'package:equatable/equatable.dart';

/// Mirror of `App\Models\Enums\UserRole` on the Laravel side.
///
/// The API serializes the PHP backed enum as its value string ("admin" /
/// "cashier") inside the login payload and `GET /api/user`.
enum UserRole { admin, cashier }

UserRole userRoleFromJson(String? value) =>
    UserRole.values.firstWhere(
      (role) => role.name == value,
      orElse: () => UserRole.cashier,
    );

/// App-wide vocabulary for what each role may reach (mirrors the Laravel
/// Gates: `access-admin-portal` = admin only, `access-pos-portal` = both).
extension UserRoleX on UserRole {
  bool get isAdmin => this == UserRole.admin;
  bool get isCashier => this == UserRole.cashier;

  /// Portal the user should land on after login.
  String get homeRoute => isAdmin ? '/admin' : '/pos';
}

/// Authenticated staff member, exactly as `AuthController@login` returns it.
class UserModel extends Equatable {
  const UserModel({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    required this.storeId,
  });

  final int id;
  final String name;
  final String email;
  final UserRole role;
  final int storeId;

  bool get isAdmin => role.isAdmin;

  factory UserModel.fromJson(Map<String, dynamic> json) {
    return UserModel(
      id: intFromJson(json['id']),
      name: stringFromJson(json['name']),
      email: stringFromJson(json['email']),
      role: userRoleFromJson(json['role']?.toString()),
      storeId: intFromJson(json['store_id']),
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'name': name,
        'email': email,
        'role': role.name,
        'store_id': storeId,
      };

  static int intFromJson(dynamic value) =>
      value is num ? value.toInt() : int.tryParse('$value') ?? 0;

  static String stringFromJson(dynamic value) => value?.toString() ?? '';

  @override
  List<Object?> get props => [id, name, email, role, storeId];
}
