class User {
  final int id;
  final String name;
  final String? phone;
  final String email;
  final String? role;
  final List<Area> areas;

  User({
    required this.id,
    required this.name,
    this.phone,
    required this.email,
    this.role,
    this.areas = const [],
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'],
      name: json['name'],
      phone: json['phone'],
      email: json['email'],
      role: json['role'],
      areas: (json['areas'] as List<dynamic>?)
              ?.map((a) => Area.fromJson(Map<String, dynamic>.from(a as Map)))
              .toList() ??
          [],
    );
  }
}

class Area {
  final int id;
  final String code;
  final String name;

  Area({required this.id, required this.code, required this.name});

  factory Area.fromJson(Map<String, dynamic> json) {
    return Area(id: json['id'], code: json['code'], name: json['name']);
  }
}
