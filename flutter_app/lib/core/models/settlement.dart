class Settlement {
  final int id;
  final String settlementNo;
  final String? settlementDate;
  final double savingsCollection;
  final double loanCollection;
  final double otherCollection;
  final double totalCollection;
  final double cashSubmitted;
  final double remainingCash;
  final String status;
  final String? officerName;
  final String? receiverName;

  Settlement({
    required this.id,
    required this.settlementNo,
    this.settlementDate,
    required this.savingsCollection,
    required this.loanCollection,
    required this.otherCollection,
    required this.totalCollection,
    required this.cashSubmitted,
    required this.remainingCash,
    required this.status,
    this.officerName,
    this.receiverName,
  });

  factory Settlement.fromJson(Map<String, dynamic> json) {
    return Settlement(
      id: json['id'],
      settlementNo: json['settlement_no'] ?? '',
      settlementDate: json['settlement_date'],
      savingsCollection: double.tryParse('${json['savings_collection'] ?? 0}') ?? 0,
      loanCollection: double.tryParse('${json['loan_collection'] ?? 0}') ?? 0,
      otherCollection: double.tryParse('${json['other_collection'] ?? 0}') ?? 0,
      totalCollection: double.tryParse('${json['total_collection'] ?? 0}') ?? 0,
      cashSubmitted: double.tryParse('${json['cash_submitted'] ?? 0}') ?? 0,
      remainingCash: double.tryParse('${json['remaining_cash'] ?? 0}') ?? 0,
      status: json['status'] ?? 'pending',
      officerName: json['officer']?['name'],
      receiverName: json['receiver']?['name'],
    );
  }
}

class CollectionSheetRow {
  final int? accountId;
  final int? loanId;
  final String accountOrLoanNo;
  final String memberName;
  final String memberNo;
  final String programName;
  final double expected;
  final double collected;
  final double due;
  final double overdue;
  final double previousBalance;
  final double currentBalance;
  final String status;
  final double? installmentAmount;
  final double? dueToday;
  final double? outstanding;

  CollectionSheetRow({
    this.accountId,
    this.loanId,
    required this.accountOrLoanNo,
    required this.memberName,
    required this.memberNo,
    required this.programName,
    required this.expected,
    required this.collected,
    required this.due,
    required this.overdue,
    required this.previousBalance,
    required this.currentBalance,
    required this.status,
    this.installmentAmount,
    this.dueToday,
    this.outstanding,
  });

  factory CollectionSheetRow.fromSavingsJson(Map<String, dynamic> json) {
    return CollectionSheetRow(
      accountId: json['account_id'],
      accountOrLoanNo: json['account_no'] ?? '',
      memberName: json['member_name'] ?? '',
      memberNo: json['member_no'] ?? '',
      programName: json['program_name'] ?? '',
      expected: double.tryParse('${json['expected'] ?? 0}') ?? 0,
      collected: double.tryParse('${json['collected'] ?? 0}') ?? 0,
      due: double.tryParse('${json['due'] ?? 0}') ?? 0,
      overdue: double.tryParse('${json['overdue'] ?? 0}') ?? 0,
      previousBalance: double.tryParse('${json['previous_balance'] ?? 0}') ?? 0,
      currentBalance: double.tryParse('${json['current_balance'] ?? 0}') ?? 0,
      status: json['status'] ?? 'due',
    );
  }

  factory CollectionSheetRow.fromLoanJson(Map<String, dynamic> json) {
    return CollectionSheetRow(
      loanId: json['loan_id'],
      accountOrLoanNo: json['loan_no'] ?? '',
      memberName: json['member_name'] ?? '',
      memberNo: json['member_no'] ?? '',
      programName: json['product_name'] ?? '',
      expected: double.tryParse('${json['installment_amount'] ?? 0}') ?? 0,
      collected: double.tryParse('${json['collected'] ?? 0}') ?? 0,
      due: double.tryParse('${json['total_due'] ?? 0}') ?? 0,
      overdue: double.tryParse('${json['overdue'] ?? 0}') ?? 0,
      previousBalance: 0,
      currentBalance: double.tryParse('${json['outstanding'] ?? 0}') ?? 0,
      status: json['status'] ?? 'due',
      installmentAmount: double.tryParse('${json['installment_amount'] ?? 0}') ?? 0,
      dueToday: double.tryParse('${json['due_today'] ?? 0}') ?? 0,
      outstanding: double.tryParse('${json['outstanding'] ?? 0}') ?? 0,
    );
  }
}
