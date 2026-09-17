import 'package:flutter/material.dart';

class AppStrings {
  final String lang;
  AppStrings(this.lang);

  bool get isBn => lang == 'bn';

  // General & Common
  String get appName => isBn ? 'একতা' : 'Ekota';
  String get orgName => isBn ? 'একতা সঞ্চয় ও ঋণদান সমবায় সমিতি' : 'Ekota Savings & Credit Cooperative Society';
  String get ok => isBn ? 'ঠিক আছে' : 'OK';
  String get cancel => isBn ? 'বাতিল' : 'Cancel';
  String get confirm => isBn ? 'নিশ্চিত করুন' : 'Confirm';
  String get save => isBn ? 'সংরক্ষণ করুন' : 'Save';
  String get submit => isBn ? 'সাবমিট করুন' : 'Submit';
  String get refresh => isBn ? 'রিফ্রেশ করুন' : 'Refresh';
  String get retry => isBn ? 'পুনরায় চেষ্টা করুন' : 'Retry';
  String get search => isBn ? 'অনুসন্ধান করুন' : 'Search';
  String get searchHint => isBn ? 'সদস্যের নাম, হিসাব নং বা ফোন দিয়ে খুঁজুন...' : 'Search member, account or phone...';
  String get all => isBn ? 'সকল' : 'All';
  String get today => isBn ? 'আজ' : 'Today';
  String get chooseDate => isBn ? 'তারিখ নির্বাচন' : 'Choose Date';
  String get loading => isBn ? 'লোড হচ্ছে...' : 'Loading...';
  String get noData => isBn ? 'কোনো তথ্য পাওয়া যায়নি' : 'No data found';
  String get date => isBn ? 'তারিখ' : 'Date';
  String get amount => isBn ? 'পরিমাণ' : 'Amount';
  String get status => isBn ? 'স্ট্যাটাস' : 'Status';
  String get actions => isBn ? 'অ্যাকশন' : 'Actions';

  // Navigation
  String get navHome => isBn ? 'হোম' : 'Home';
  String get navSavings => isBn ? 'সঞ্চয়' : 'Savings';
  String get navLoans => isBn ? 'লোন' : 'Loans';
  String get navMembers => isBn ? 'সদস্যবৃন্দ' : 'Members';
  String get navClosing => isBn ? 'ক্লোজিং' : 'Closing';

  // Drawer
  String get fieldOfficer => isBn ? 'দায়িত্বপ্রাপ্ত অফিসার' : 'Field Officer';
  String get mainMenu => isBn ? 'প্রধান মেনু' : 'Main Menu';
  String get dashboard => isBn ? 'ড্যাশবোর্ড' : 'Dashboard';
  String get memberList => isBn ? 'সদস্য তালিকা' : 'Member List';
  String get collectionAndRecovery => isBn ? 'কালেকশন ও আদায়' : 'Collection & Recovery';
  String get savingsCollection => isBn ? 'সঞ্চয় আদায়' : 'Savings Collection';
  String get loanCollection => isBn ? 'লোন কিস্তি আদায়' : 'Loan Installment Collection';
  String get collectionSheet => isBn ? 'কালেকশন শিট' : 'Collection Sheet';
  String get collectionHistory => isBn ? 'কালেকশন হিস্ট্রি ও রিপোর্ট' : 'Collection History & Report';
  String get cashSettlement => isBn ? 'দৈনিক ক্যাশ সেটেলমেন্ট' : 'Daily Cash Settlement';
  String get accountManagement => isBn ? 'হিসাব ও লোন ব্যবস্থাপনা' : 'Account & Loan Management';
  String get savingsAccounts => isBn ? 'সঞ্চয় হিসাবসমূহ' : 'Savings Accounts';
  String get activeLoans => isBn ? 'চলমান লোন তালিকা' : 'Active Loans List';
  String get newLoanApplication => isBn ? 'নতুন লোন আবেদন' : 'New Loan Application';
  String get onlineMode => isBn ? 'অনলাইন মোড' : 'Online Mode';
  String get offlineMode => isBn ? 'অফলাইন মোড' : 'Offline Mode';
  String get logout => isBn ? 'লগআউট' : 'Logout';
  String get logoutConfirmTitle => isBn ? 'লগআউট করতে চান?' : 'Logout?';
  String get logoutConfirmMsg => isBn ? 'আপনি কি নিশ্চিত যে আপনার একাউন্ট থেকে লগআউট করতে চান?' : 'Are you sure you want to log out of your account?';
  String get exitAppTitle => isBn ? 'অ্যাপ থেকে বের হতে চান?' : 'Exit App?';
  String get exitAppMsg => isBn ? 'আপনি কি নিশ্চিত যে একতা অ্যাপ বন্ধ করতে চান?' : 'Are you sure you want to close Ekota app?';
  String get exitApp => isBn ? 'বের হন' : 'Exit';

  // Frequencies
  String get daily => isBn ? 'দৈনিক' : 'Daily';
  String get weekly => isBn ? 'সাপ্তাহিক' : 'Weekly';
  String get monthly => isBn ? 'মাসিক' : 'Monthly';

  // Collection Stats & Badges
  String get target => isBn ? 'লক্ষ্যমাত্রা' : 'Target';
  String get collected => isBn ? 'আদায় হয়েছে' : 'Collected';
  String get due => isBn ? 'বকেয়া' : 'Due';
  String get overdue => isBn ? 'খেলাপি' : 'Overdue';
  String get balance => isBn ? 'মোট সঞ্চয়' : 'Balance';
  String get outstanding => isBn ? 'অবশিষ্ট ঋণ স্থিতি' : 'Outstanding';
  String get installment => isBn ? 'কিস্তির পরিমাণ' : 'Installment';
  String get dueToday => isBn ? 'আজকের কিস্তি' : 'Due Today';
  String get paidInFull => isBn ? 'পরিশোধ সম্পন্ন' : 'Paid in Full';
  String get collectDeposit => isBn ? 'জমা নিন' : 'Collect';
  String get collectInstallment => isBn ? 'কিস্তি নিন' : 'Collect';
  String get addMore => isBn ? '+ আরও জমা' : '+ Add More';
  String get submitAllDue => isBn ? 'একসাথে সকল বকেয়া জমা দিন' : 'Submit All Due';

  // Status Badges
  String get statusPaid => isBn ? 'পরিশোধিত' : 'PAID';
  String get statusActive => isBn ? 'সক্রিয়' : 'ACTIVE';
  String get statusPartial => isBn ? 'আংশিক আদায়' : 'PARTIAL';
  String get statusDue => isBn ? 'বকেয়া' : 'DUE';
  String get statusOverdue => isBn ? 'খেলাপি' : 'OVERDUE';
  String get statusPending => isBn ? 'অপেক্ষমাণ' : 'PENDING';
  String get statusApproved => isBn ? 'অনুমোদিত' : 'APPROVED';
  String get statusClosed => isBn ? 'বন্ধ' : 'CLOSED';
  String get statusInactive => isBn ? 'নিষ্ক্রিয়' : 'INACTIVE';
  String get statusCancelled => isBn ? 'বাতিল' : 'CANCELLED';

  // Language switch
  String get language => isBn ? 'ভাষা' : 'Language';
  String get bangla => 'বাংলা';
  String get english => 'English';

  static AppStrings of(BuildContext context) {
    final locale = Localizations.localeOf(context);
    return AppStrings(locale.languageCode);
  }

  static AppStrings fromCode(String code) {
    return AppStrings(code);
  }
}

extension AppStringsExtension on BuildContext {
  AppStrings get l10n => AppStrings.of(this);
  bool get isBn => Localizations.localeOf(this).languageCode == 'bn';
}
