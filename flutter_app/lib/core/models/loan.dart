class Loan {
  final int id;
  final String loanNo;
  final double principalAmount;
  final double outstanding;
  final double installmentAmount;
  final double totalPaid;
  final String frequency;
  final String status;
  final String? memberName;
  final String? memberNo;
  final String? productName;
  final String? areaName;
  final List<LoanSchedule>? schedules;
  final List<LoanTransaction>? transactions;

  Loan({
    required this.id,
    required this.loanNo,
    required this.principalAmount,
    required this.outstanding,
    required this.installmentAmount,
    required this.totalPaid,
    required this.frequency,
    required this.status,
    this.memberName,
    this.memberNo,
    this.productName,
    this.areaName,
    this.schedules,
    this.transactions,
  });

  factory Loan.fromJson(Map<String, dynamic> json) {
    return Loan(
      id: json['id'],
      loanNo: json['loan_no'] ?? '',
      principalAmount: double.tryParse('${json['principal_amount'] ?? 0}') ?? 0,
      outstanding: double.tryParse('${json['outstanding'] ?? 0}') ?? 0,
      installmentAmount: double.tryParse('${json['installment_amount'] ?? 0}') ?? 0,
      totalPaid: double.tryParse('${json['total_paid'] ?? 0}') ?? 0,
      frequency: json['frequency'] ?? '',
      status: json['status'] ?? '',
      memberName: json['member']?['name'] ?? json['member_name'],
      memberNo: json['member']?['member_no'] ?? json['member_no'],
      productName: json['product']?['name'] ?? json['product_name'],
      areaName: json['area']?['name'],
      schedules: json['schedules'] != null
          ? (json['schedules'] as List)
              .map((s) => LoanSchedule.fromJson(Map<String, dynamic>.from(s as Map)))
              .toList()
          : null,
      transactions: json['transactions'] != null
          ? (json['transactions'] as List)
              .map((t) => LoanTransaction.fromJson(Map<String, dynamic>.from(t as Map)))
              .toList()
          : null,
    );
  }
}

class LoanSchedule {
  final int installmentNo;
  final String? dueDate;
  final double total;
  final double paid;
  final String status;

  LoanSchedule({
    required this.installmentNo,
    this.dueDate,
    required this.total,
    required this.paid,
    required this.status,
  });

  factory LoanSchedule.fromJson(Map<String, dynamic> json) {
    return LoanSchedule(
      installmentNo: json['installment_no'] ?? 0,
      dueDate: json['due_date'],
      total: double.tryParse('${json['total'] ?? 0}') ?? 0,
      paid: double.tryParse('${json['paid'] ?? 0}') ?? 0,
      status: json['status'] ?? 'due',
    );
  }
}

class LoanTransaction {
  final int id;
  final String txnNo;
  final String type;
  final double amount;
  final double principalPaid;
  final double interestPaid;
  final double lateFee;
  final String? date;
  final String? memberName;
  final String? loanNo;
  final String? paymentMethod;

  LoanTransaction({
    required this.id,
    required this.txnNo,
    required this.type,
    required this.amount,
    required this.principalPaid,
    required this.interestPaid,
    required this.lateFee,
    this.date,
    this.memberName,
    this.loanNo,
    this.paymentMethod,
  });

  factory LoanTransaction.fromJson(Map<String, dynamic> json) {
    return LoanTransaction(
      id: json['id'],
      txnNo: json['txn_no'] ?? '',
      type: json['type'] ?? '',
      amount: double.tryParse('${json['amount'] ?? 0}') ?? 0,
      principalPaid: double.tryParse('${json['principal_paid'] ?? 0}') ?? 0,
      interestPaid: double.tryParse('${json['interest_paid'] ?? 0}') ?? 0,
      lateFee: double.tryParse('${json['late_fee'] ?? 0}') ?? 0,
      date: json['txn_date'] ?? json['date'],
      memberName: json['member']?['name'] ?? json['member_name'],
      loanNo: json['loan']?['loan_no'] ?? json['loan_no'],
      paymentMethod: json['payment_method'],
    );
  }
}

class LoanProduct {
  final int id;
  final String code;
  final String name;
  final String frequency;
  final double interestRate;
  final String interestType;
  final double minAmount;
  final double maxAmount;
  final int minTerm;
  final int maxTerm;
  final double processingFee;
  final double insuranceFee;
  final String? description;

  LoanProduct({
    required this.id,
    required this.code,
    required this.name,
    required this.frequency,
    required this.interestRate,
    this.interestType = 'flat',
    required this.minAmount,
    required this.maxAmount,
    required this.minTerm,
    required this.maxTerm,
    this.processingFee = 0,
    this.insuranceFee = 0,
    this.description,
  });

  factory LoanProduct.fromJson(Map<String, dynamic> json) {
    return LoanProduct(
      id: json['id'],
      code: json['code'] ?? '',
      name: json['name'] ?? '',
      frequency: json['frequency'] ?? 'monthly',
      interestRate: double.tryParse('${json['interest_rate'] ?? 0}') ?? 0,
      interestType: json['interest_type'] ?? 'flat',
      minAmount: double.tryParse('${json['min_amount'] ?? 0}') ?? 0,
      maxAmount: double.tryParse('${json['max_amount'] ?? 0}') ?? 0,
      minTerm: int.tryParse('${json['min_term'] ?? 1}') ?? 1,
      maxTerm: int.tryParse('${json['max_term'] ?? 12}') ?? 12,
      processingFee: double.tryParse('${json['processing_fee'] ?? 0}') ?? 0,
      insuranceFee: double.tryParse('${json['insurance_fee'] ?? 0}') ?? 0,
      description: json['description'],
    );
  }
}

