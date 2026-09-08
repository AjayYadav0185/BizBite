import 'package:equatable/equatable.dart';

/// Menu category — payload of `GET /api/menu`:
/// `"categories": [{ "id": 1, "name": "Snacks" }, ...]`
class CategoryModel extends Equatable {
  const CategoryModel({required this.id, required this.name});

  final int id;
  final String name;

  factory CategoryModel.fromJson(Map<String, dynamic> json) => CategoryModel(
        id: _int(json['id']),
        name: _string(json['name']),
      );

  Map<String, dynamic> toJson() => {'id': id, 'name': name};

  static int _int(dynamic v) =>
      v is num ? v.toInt() : int.tryParse('$v') ?? 0;
  static String _string(dynamic v) => v?.toString() ?? '';

  @override
  List<Object?> get props => [id, name];
}
