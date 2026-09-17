<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\AreaController;
use App\Http\Controllers\Admin\FieldOfficerController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Member\MemberController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\Savings\SavingsProgramController;
use App\Http\Controllers\Savings\SavingsAccountController;
use App\Http\Controllers\Savings\SavingsDepositController;
use App\Http\Controllers\Savings\SavingsWithdrawalController;
use App\Http\Controllers\Savings\ReceiptController;
use App\Http\Controllers\Loan\LoanProductController;
use App\Http\Controllers\Loan\LoanApplicationController;
use App\Http\Controllers\Loan\LoanController;
use App\Http\Controllers\Collection\CollectionController;
use App\Http\Controllers\Collection\SettlementController;
use App\Http\Controllers\Cash\CashRegisterController;
use App\Http\Controllers\Cash\ExpenseController;
use App\Http\Controllers\Cash\IncomeController;
use App\Http\Controllers\Cash\ExpenseCategoryController;
use App\Http\Controllers\Cash\IncomeCategoryController;
use App\Http\Controllers\Cash\BankAccountController;
use App\Http\Controllers\Reports\ReportController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\Fund\FundController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AuthController::class, 'showLoginForm'])->name('home');

Route::get('/language/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'bn'], true)) {
        session(['locale' => $locale]);
    }

    return redirect()->back();
})->name('language.switch');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('areas/{area}/officers', [AreaController::class, 'officers'])->name('areas.officers');
    Route::resource('areas', AreaController::class)->except(['show']);
    Route::resource('field-officers', FieldOfficerController::class);
    Route::resource('users', UserController::class)->except(['show']);
    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::get('settings/permissions', [PermissionController::class, 'index'])->name('permissions.index');
    Route::post('settings/permissions', [PermissionController::class, 'update'])->name('permissions.update');

    Route::get('members/search', [MemberController::class, 'quickSearch'])->name('members.search');
    Route::resource('members', MemberController::class);
    Route::get('onboarding', [OnboardingController::class, 'create'])->name('onboarding.create');
    Route::post('onboarding', [OnboardingController::class, 'store'])->name('onboarding.store');
    Route::get('members/{member}/statement', [MemberController::class, 'statement'])->name('members.statement');
    Route::get('members/{member}/statement/pdf', [MemberController::class, 'statementPdf'])->name('members.statement.pdf');
    Route::post('members/{member}/documents', [MemberController::class, 'storeDocument'])->name('members.documents.store');
    Route::delete('members/documents/{document}', [MemberController::class, 'destroyDocument'])->name('members.documents.destroy');
    Route::post('members/{member}/nominees', [MemberController::class, 'storeNominee'])->name('members.nominees.store');
    Route::delete('members/nominees/{nominee}', [MemberController::class, 'destroyNominee'])->name('members.nominees.destroy');

    Route::prefix('savings')->name('savings.')->group(function () {
        Route::resource('programs', SavingsProgramController::class)->except(['show']);
        Route::get('accounts/member-details/{member}', [SavingsAccountController::class, 'memberDetails'])->name('accounts.member-details');
        Route::resource('accounts', SavingsAccountController::class)->except(['show']);
        Route::get('accounts/{account}/transactions', [SavingsAccountController::class, 'transactions'])->name('accounts.transactions');
        Route::get('accounts/{account}/close', [SavingsAccountController::class, 'closeForm'])->name('accounts.close-form');
        Route::post('accounts/{account}/close', [SavingsAccountController::class, 'close'])->name('accounts.close');

        Route::get('deposits', [SavingsDepositController::class, 'index'])->name('deposits.index');
        Route::get('deposits/create', [SavingsDepositController::class, 'create'])->name('deposits.create');
        Route::post('deposits', [SavingsDepositController::class, 'store'])->name('deposits.store');

        Route::get('withdrawals', [SavingsWithdrawalController::class, 'index'])->name('withdrawals.index');
        Route::get('withdrawals/create', [SavingsWithdrawalController::class, 'create'])->name('withdrawals.create');
        Route::post('withdrawals', [SavingsWithdrawalController::class, 'store'])->name('withdrawals.store');

        Route::get('receipts/{type}/{id}', [ReceiptController::class, 'show'])->name('receipts.show');
        Route::get('receipts/{type}/{id}/pdf', [ReceiptController::class, 'pdf'])->name('receipts.pdf');

        Route::post('transactions/{transaction}/reverse', [SavingsAccountController::class, 'reverse'])->name('transactions.reverse');
    });

    Route::prefix('loans')->name('loans.')->group(function () {
        Route::resource('products', LoanProductController::class)->except(['show']);
        Route::get('applications/member-details/{member}', [LoanApplicationController::class, 'memberDetails'])->name('applications.member-details');
        Route::resource('applications', LoanApplicationController::class)->except(['edit', 'update', 'destroy']);

        Route::get('/', [LoanController::class, 'index'])->name('index');
        Route::get('overdue', [LoanController::class, 'overdue'])->name('overdue');
        Route::get('repayments', [LoanController::class, 'repayments'])->name('repayments.index');
        Route::post('repayments/{transaction}/reverse', [LoanController::class, 'reverseRepayment'])->name('repayments.reverse');
        Route::post('{loan}/write-off', [LoanController::class, 'writeOff'])->name('write-off');
        Route::get('{loan}', [LoanController::class, 'show'])->name('show');
        Route::get('{loan}/edit', [LoanController::class, 'edit'])->name('edit');
        Route::put('{loan}', [LoanController::class, 'update'])->name('update');
        Route::delete('{loan}', [LoanController::class, 'destroy'])->name('destroy');
        Route::get('{loan}/repay', [LoanController::class, 'repayForm'])->name('repay');
        Route::post('{loan}/repay', [LoanController::class, 'repay'])->name('repay.store');
        Route::get('{loan}/schedule', [LoanController::class, 'schedule'])->name('schedule');
        Route::get('{loan}/collection-sheet', [LoanController::class, 'collectionSheet'])->name('collection-sheet');
    });

    Route::prefix('collection')->name('collection.')->group(function () {
        Route::get('savings', fn () => redirect()->route('collection.savings.sheet', 'daily'))->name('savings.index');
        Route::get('loans', fn () => redirect()->route('collection.loans.sheet', 'daily'))->name('loans.index');
        Route::get('savings/{frequency}', [CollectionController::class, 'savingsSheet'])->whereIn('frequency', ['daily', 'weekly', 'monthly'])->name('savings.sheet');
        Route::get('savings/{frequency}/details', [CollectionController::class, 'savingsDetails'])->whereIn('frequency', ['daily', 'weekly', 'monthly'])->name('savings.details');
        Route::get('savings/{frequency}/deposit', [CollectionController::class, 'savingsDepositForm'])->whereIn('frequency', ['daily', 'weekly', 'monthly'])->name('savings.deposit');
        Route::post('savings/{frequency}', [CollectionController::class, 'savingsStore'])->whereIn('frequency', ['daily', 'weekly', 'monthly'])->name('savings.store');
        Route::post('savings/transactions/{transaction}/edit', [CollectionController::class, 'savingsTransactionUpdate'])->name('savings.transactions.update');
        Route::post('savings/transactions/{transaction}/delete', [CollectionController::class, 'savingsTransactionDestroy'])->name('savings.transactions.destroy');
        Route::get('savings/{frequency}/print', [CollectionController::class, 'savingsPrint'])->whereIn('frequency', ['daily', 'weekly', 'monthly'])->name('savings.print');
        Route::get('loans/{frequency}', [CollectionController::class, 'loanSheet'])->whereIn('frequency', ['daily', 'weekly', 'monthly'])->name('loans.sheet');
        Route::get('loans/{frequency}/details', [CollectionController::class, 'loanDetails'])->whereIn('frequency', ['daily', 'weekly', 'monthly'])->name('loans.details');
        Route::get('loans/{frequency}/repay', [CollectionController::class, 'loanRepayForm'])->whereIn('frequency', ['daily', 'weekly', 'monthly'])->name('loans.repay');
        Route::post('loans/{frequency}', [CollectionController::class, 'loanStore'])->whereIn('frequency', ['daily', 'weekly', 'monthly'])->name('loans.store');
        Route::post('loans/transactions/{transaction}/edit', [CollectionController::class, 'loanTransactionUpdate'])->name('loans.transactions.update');
        Route::post('loans/transactions/{transaction}/delete', [CollectionController::class, 'loanTransactionDestroy'])->name('loans.transactions.destroy');
        Route::get('loans/{frequency}/print', [CollectionController::class, 'loanPrint'])->whereIn('frequency', ['daily', 'weekly', 'monthly'])->name('loans.print');
        Route::resource('settlements', SettlementController::class)->except(['show', 'edit', 'update']);
        Route::post('settlements/{settlement}/receive', [SettlementController::class, 'receive'])->name('settlements.receive');
    });

    Route::prefix('cash')->name('cash.')->group(function () {
        Route::get('register', [CashRegisterController::class, 'index'])->name('register');
        Route::post('register/open', [CashRegisterController::class, 'open'])->name('register.open');
        Route::post('register/close', [CashRegisterController::class, 'close'])->name('register.close');
        Route::post('register/transactions', [CashRegisterController::class, 'storeTransaction'])->name('register.transactions.store');
        Route::post('transactions/{transaction}/reverse', [CashRegisterController::class, 'reverse'])->name('register.transactions.reverse');
        Route::resource('expenses', ExpenseController::class)->except(['show']);
        Route::resource('incomes', IncomeController::class)->except(['show']);
        Route::resource('expense-categories', ExpenseCategoryController::class)->except(['show']);
        Route::resource('income-categories', IncomeCategoryController::class)->except(['show']);
    });

    Route::resource('bank-accounts', BankAccountController::class)->except(['show']);
    Route::post('bank-accounts/{bankAccount}/transactions', [BankAccountController::class, 'storeTransaction'])->name('bank-accounts.transactions.store');

    Route::get('funds/disburse', [FundController::class, 'disburseCreate'])->name('funds.disburse-form');
    Route::post('funds/disburse', [FundController::class, 'globalDisburse'])->name('funds.global-disburse');
    Route::post('funds/{fund}/disburse', [FundController::class, 'disburse'])->name('funds.disburse');
    Route::post('funds/{fund}/contribute', [FundController::class, 'contribute'])->name('funds.contribute');
    Route::resource('funds', FundController::class);

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('summary', [ReportController::class, 'summary'])->name('summary');
        Route::get('summary/pdf', [ReportController::class, 'summaryPdf'])->name('summary.pdf');
        Route::get('sheet', [ReportController::class, 'sheet'])->name('sheet');
        Route::get('sheet/pdf', [ReportController::class, 'sheetPdf'])->name('sheet.pdf');
        Route::get('members', [ReportController::class, 'members'])->name('members');
        Route::get('savings', [ReportController::class, 'savings'])->name('savings');
        Route::get('loans', [ReportController::class, 'loans'])->name('loans');
        Route::get('collections', [ReportController::class, 'collections'])->name('collections');
        Route::get('cash', [ReportController::class, 'cash'])->name('cash');
        Route::get('areas', [ReportController::class, 'areas'])->name('areas');
        Route::get('officers', [ReportController::class, 'officers'])->name('officers');
        Route::get('export', [ReportController::class, 'export'])->name('export');
    });

    Route::get('audit', [AuditController::class, 'index'])->name('audit.index');
});

Route::get('/receipts/{type}/{id}', [ReceiptController::class, 'showPublic'])->name('receipts.show-public');