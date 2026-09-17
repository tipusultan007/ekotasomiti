class SavingsAccount {
  final int id;
  final String accountNo;
  final double currentBalance;
  final double expectedDeposit;
  final double minDeposit;
  final String status;
  final int? memberId;
  final String? memberName;
  final String? memberNo;
  final String? memberPhone;
  final String? programName;
  final String? programCode;
  final String? areaName;
  final String? openingDate;
  final double? openingBalance;
  final String? fieldOfficerName;
  final List<SavingsTransaction>? transactions;

  SavingsAccount({
    required this.id,
    required this.accountNo,
    required this.currentBalance,
    required this.expectedDeposit,
    required this.minDeposit,
    required this.status,
    this.memberId,
    this.memberName,
    this.memberNo,
    this.memberPhone,
    this.programName,
    this.programCode,
    this.areaName,
    this.openingDate,
    this.openingBalance,
    this.fieldOfficerName,
    this.transactions,
  });

  factory SavingsAccount.fromJson(Map<String, dynamic> json) {
    return SavingsAccount(
      id: json['id'],
      accountNo: json['account_no'] ?? '',
      currentBalance: double.tryParse('${json['current_balance'] ?? 0}') ?? 0,
      expectedDeposit: double.tryParse('${json['expected_deposit'] ?? 0}') ?? 0,
      minDeposit: double.tryParse('${json['min_deposit'] ?? 0}') ?? 0,
      status: json['status'] ?? 'active',
      memberId: json['member_id'] ?? json['member']?['id'],
      memberName: json['member']?['name'] ?? json['member_name'],
      memberNo: json['member']?['member_no'] ?? json['member_no'],
      memberPhone: json['member']?['mobile']?.toString() ?? json['mobile']?.toString(),
      programName: json['program']?['name'] ?? json['program_name'],
      programCode: json['program']?['code'] ?? json['program_code'],
      areaName: json['area']?['name'] ?? json['area_name'],
      openingDate: json['opening_date']?.toString(),
      openingBalance: double.tryParse('${json['opening_balance'] ?? 0}'),
      fieldOfficerName: json['field_officer']?['name'] ?? json['field_officer_name'],
      transactions: json['transactions'] != null
          ? (json['transactions'] as List)
              .map((t) => SavingsTransaction.fromJson(Map<String, dynamic>.from(t as Map)))
              .toList()
          : null,
    );
  }
}

class SavingsTransaction {
  final int id;
  final String txnNo;
  final String type;
  final double amount;
  final String? date;
  final double? balanceAfter;
  final String? accountNo;
  final String? memberName;
  final String? programName;
  final String? paymentMethod;

  SavingsTransaction({
    required this.id,
    required this.txnNo,
    required this.type,
    required this.amount,
    this.date,
    this.balanceAfter,
    this.accountNo,
    this.memberName,
    this.programName,
    this.paymentMethod,
  });

  factory SavingsTransaction.fromJson(Map<String, dynamic> json) {
    return SavingsTransaction(
      id: json['id'],
      txnNo: json['txn_no'] ?? '',
      type: json['type'] ?? '',
      amount: double.tryParse('${json['amount'] ?? 0}') ?? 0,
      date: json['txn_date'] ?? json['date'],
      balanceAfter: double.tryParse('${json['balance_after'] ?? 0}'),
      accountNo: json['account']?['account_no'] ?? json['account_no'],
      memberName: json['member']?['name'] ?? json['member_name'],
      programName: json['program']?['name'] ?? json['program'],
      paymentMethod: json['payment_method'],
    );
  }
}

class SavingsProgram {
  final int id;
  final String code;
  final String name;
  final String frequency;
  final double? minDeposit;
  final double? expectedDeposit;

  SavingsProgram({
    required this.id,
    required this.code,
    required this.name,
    required this.frequency,
    this.minDeposit,
    this.expectedDeposit,
  });

  factory SavingsProgram.fromJson(Map<String, dynamic> json) {
    return SavingsProgram(
      id: json['id'],
      code: json['code'] ?? '',
      name: json['name'] ?? '',
      frequency: json['frequency'] ?? 'daily',
      minDeposit: double.tryParse('${json['min_deposit'] ?? 0}'),
      expectedDeposit: double.tryParse('${json['expected_deposit'] ?? 0}'),
    );
  }
}
