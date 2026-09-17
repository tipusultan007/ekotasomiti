class Member {
  final int id;
  final String memberNo;
  final String name;
  final String? nameBn;
  final String? fatherHusbandName;
  final String? motherName;
  final String? dob;
  final String? mobile;
  final String? nid;
  final String? address;
  final String? occupation;
  final String? gender;
  final String status;
  final String? notes;
  final String? membershipDate;
  final String? photoUrl;
  final String? photoPath;
  final AreaInfo? area;
  final UserInfo? fieldOfficer;
  final List<SavingsAccountInfo>? savingsAccounts;
  final List<LoanInfo>? loans;

  Member({
    required this.id,
    required this.memberNo,
    required this.name,
    this.nameBn,
    this.fatherHusbandName,
    this.motherName,
    this.dob,
    this.mobile,
    this.nid,
    this.address,
    this.occupation,
    this.gender,
    required this.status,
    this.notes,
    this.membershipDate,
    this.photoUrl,
    this.photoPath,
    this.area,
    this.fieldOfficer,
    this.savingsAccounts,
    this.loans,
  });

  factory Member.fromJson(Map<String, dynamic> json) {
    return Member(
      id: json['id'],
      memberNo: json['member_no'] ?? '',
      name: json['name'] ?? '',
      nameBn: json['name_bn'],
      fatherHusbandName: json['father_husband_name'],
      motherName: json['mother_name'],
      dob: json['dob'],
      mobile: json['mobile'],
      nid: json['nid'],
      address: json['address'],
      occupation: json['occupation'],
      gender: json['gender'],
      status: json['status'] ?? 'active',
      notes: json['notes'],
      membershipDate: json['membership_date'],
      photoUrl: json['photo_url'] ?? json['photo_path']?.toString(),
      photoPath: json['photo_path'],
      area: json['area'] != null ? AreaInfo.fromJson(Map<String, dynamic>.from(json['area'] as Map)) : null,
      fieldOfficer: json['field_officer'] != null ? UserInfo.fromJson(Map<String, dynamic>.from(json['field_officer'] as Map)) : null,
      savingsAccounts: json['savings_accounts'] != null
          ? (json['savings_accounts'] as List)
              .map((a) => SavingsAccountInfo.fromJson(Map<String, dynamic>.from(a as Map)))
              .toList()
          : null,
      loans: json['loans'] != null
          ? (json['loans'] as List)
              .map((l) => LoanInfo.fromJson(Map<String, dynamic>.from(l as Map)))
              .toList()
          : null,
    );
  }
}

class AreaInfo {
  final int id;
  final String code;
  final String name;

  AreaInfo({required this.id, required this.code, required this.name});
  factory AreaInfo.fromJson(Map<String, dynamic> json) =>
      AreaInfo(id: json['id'], code: json['code'] ?? '', name: json['name'] ?? '');
}

class UserInfo {
  final int id;
  final String name;

  UserInfo({required this.id, required this.name});
  factory UserInfo.fromJson(Map<String, dynamic> json) =>
      UserInfo(id: json['id'], name: json['name'] ?? '');
}

class SavingsAccountInfo {
  final int id;
  final String accountNo;
  final double currentBalance;
  final double expectedDeposit;
  final String? programName;

  SavingsAccountInfo({
    required this.id,
    required this.accountNo,
    required this.currentBalance,
    required this.expectedDeposit,
    this.programName,
  });

  factory SavingsAccountInfo.fromJson(Map<String, dynamic> json) {
    return SavingsAccountInfo(
      id: json['id'],
      accountNo: json['account_no'] ?? '',
      currentBalance: double.tryParse('${json['current_balance'] ?? 0}') ?? 0,
      expectedDeposit: double.tryParse('${json['expected_deposit'] ?? 0}') ?? 0,
      programName: json['program']?['name'],
    );
  }
}

class LoanInfo {
  final int id;
  final String loanNo;
  final double principalAmount;
  final double outstanding;
  final double installmentAmount;
  final String status;
  final String? productName;

  LoanInfo({
    required this.id,
    required this.loanNo,
    required this.principalAmount,
    required this.outstanding,
    required this.installmentAmount,
    required this.status,
    this.productName,
  });

  factory LoanInfo.fromJson(Map<String, dynamic> json) {
    return LoanInfo(
      id: json['id'],
      loanNo: json['loan_no'] ?? '',
      principalAmount: double.tryParse('${json['principal_amount'] ?? 0}') ?? 0,
      outstanding: double.tryParse('${json['outstanding'] ?? 0}') ?? 0,
      installmentAmount: double.tryParse('${json['installment_amount'] ?? 0}') ?? 0,
      status: json['status'] ?? '',
      productName: json['product']?['name'],
    );
  }
}
