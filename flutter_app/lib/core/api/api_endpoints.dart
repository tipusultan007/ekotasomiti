class ApiEndpoints {
  ApiEndpoints._();

  static const String host = 'https://ekota.poddalimited.com';
  static const String baseUrl = '$host/api';

  static const String login = '/auth/login';
  static const String logout = '/auth/logout';
  static const String me = '/auth/me';
  static const String updateProfile = '/auth/profile';
  static const String updatePassword = '/auth/password';

  static const String dashboard = '/dashboard';
  static const String areas = '/areas';

  static const String members = '/members';
  static const String membersSearch = '/members/search';
  static const String onboarding = '/onboarding';

  static const String savingsPrograms = '/savings/programs';
  static const String savingsAccounts = '/savings/accounts';
  static const String savingsMemberDetails = '/savings/accounts/member-details';
  static const String savingsTransactions = '/savings/transactions';

  static const String loans = '/loans';
  static const String loanProducts = '/loans/products';
  static const String loanApplications = '/loans/applications';
  static const String loanMemberDetails = '/loans/applications/member-details';

  static const String collectionSavings = '/collection/savings';
  static const String collectionLoans = '/collection/loans';
  static const String savingsDeposit = '/collection/savings/deposit';
  static const String loanRepay = '/collection/loans/repay';
  static const String collectionSync = '/collection/sync';

  static const String settlements = '/settlements';
  static const String settlementPreview = '/settlements/preview';

  static const String receipts = '/receipts';

  static const String reportsCollections = '/reports/collections';
}
