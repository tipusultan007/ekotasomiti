<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\CashRegister;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Income;
use App\Models\IncomeCategory;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanProduct;
use App\Models\Member;
use App\Models\MemberNominee;
use App\Models\SavingsAccount;
use App\Models\SavingsProgram;
use App\Models\User;
use App\Services\AccountNumberService;
use App\Services\CashSettlementService;
use App\Services\CashTransactionService;
use App\Services\LoanDisbursementService;
use App\Services\LoanRepaymentService;
use App\Services\LoanScheduleService;
use App\Services\SavingsTransactionService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

class DemoDataSeeder extends Seeder
{
    protected AccountNumberService $numbers;

    protected CashTransactionService $cash;

    protected SavingsTransactionService $savings;

    protected LoanDisbursementService $disburse;

    protected LoanRepaymentService $repay;

    protected CashSettlementService $settlements;

    public function run(): void
    {
        $this->numbers = app(AccountNumberService::class);
        $this->cash = app(CashTransactionService::class);
        $this->savings = app(SavingsTransactionService::class);
        $this->disburse = app(LoanDisbursementService::class);
        $this->repay = app(LoanRepaymentService::class);
        $this->settlements = app(CashSettlementService::class);

        $manager = $this->seedUsers();

        Auth::login($manager);

        $areas = Area::all()->keyBy('code');
        $officers = $this->seedOfficers($areas);

        $members = $this->seedMembers($officers, $areas);

        $this->seedSavings($members, $officers, $areas);

        $this->seedLoans($members, $officers, $areas);

        $this->seedCashBook($manager);

        $this->seedWithdrawals($members);

        $this->seedSettlements($officers, $manager);

        $this->command->info('Demo data seeded. Log in with demo users: admin@ekota.com / officer0@ekota.com / manager@ekota.com / cashier@ekota.com / accountant@ekota.com (password).');
    }

    protected function seedUsers(): User
    {
        $demo = [
            ['name' => 'Manager', 'email' => 'manager@ekota.com', 'role' => 'manager', 'phone' => '01711110001'],
            ['name' => 'Cashier', 'email' => 'cashier@ekota.com', 'role' => 'cashier', 'phone' => '01711110002'],
            ['name' => 'Accountant', 'email' => 'accountant@ekota.com', 'role' => 'accountant', 'phone' => '01711110003'],
        ];

        foreach ($demo as $def) {
            $user = User::firstOrCreate(
                ['email' => $def['email']],
                ['name' => $def['name'], 'phone' => $def['phone'], 'password' => 'password', 'is_active' => true]
            );
            $user->assignRole($def['role']);
        }

        return User::where('email', 'manager@ekota.com')->first();
    }

    protected function seedOfficers($areas): array
    {
        $defs = [
            ['code' => 'FO-01', 'name' => 'Rahim Uddin', 'mobile' => '01810000001', 'nid' => '19901234567001', 'area' => 'CTG-01'],
            ['code' => 'FO-02', 'name' => 'Karim Miah', 'mobile' => '01810000002', 'nid' => '19901234567002', 'area' => 'CTG-01'],
            ['code' => 'FO-03', 'name' => 'Hasina Begum', 'mobile' => '01810000003', 'nid' => '19901234567003', 'area' => 'CTG-02'],
            ['code' => 'FO-04', 'name' => 'Jamal Hossain', 'mobile' => '01810000004', 'nid' => '19901234567004', 'area' => 'CTG-02'],
            ['code' => 'FO-05', 'name' => 'Nasrin Akter', 'mobile' => '01810000005', 'nid' => '19901234567005', 'area' => 'CTG-03'],
        ];

        $officers = [];

        foreach ($defs as $i => $def) {
            $user = User::firstOrCreate(
                ['email' => "officer{$i}@ekota.com"],
                ['name' => $def['name'], 'phone' => $def['mobile'], 'password' => 'password', 'is_active' => true]
            );
            $user->assignRole('field_officer');
            $user->areas()->syncWithoutDetaching([$areas[$def['area']]->id]);

            $officers[] = $user;
        }

        return $officers;
    }

    protected function seedMembers(array $officers, $areas): array
    {
        $defs = [
            ['name' => 'Abdul Khaleque', 'gender' => 'male', 'occupation' => 'Rickshaw Puller'],
            ['name' => 'Fatema Khatun', 'gender' => 'female', 'occupation' => 'Garment Worker'],
            ['name' => 'Md. Sirajul Islam', 'gender' => 'male', 'occupation' => 'Shopkeeper'],
            ['name' => 'Rokeya Begum', 'gender' => 'female', 'occupation' => 'Home Maker'],
            ['name' => 'Nurul Amin', 'gender' => 'male', 'occupation' => 'Day Laborer'],
            ['name' => 'Shirin Akter', 'gender' => 'female', 'occupation' => 'Tailor'],
            ['name' => 'Jahangir Alam', 'gender' => 'male', 'occupation' => 'Vegetable Vendor'],
            ['name' => 'Maksuda Begum', 'gender' => 'female', 'occupation' => 'Food Seller'],
            ['name' => 'Mozammel Haque', 'gender' => 'male', 'occupation' => 'CNG Driver'],
            ['name' => 'Salma Parvin', 'gender' => 'female', 'occupation' => 'Beautician'],
            ['name' => 'Abdus Salam', 'gender' => 'male', 'occupation' => 'Fisherman'],
            ['name' => 'Rehana Akter', 'gender' => 'female', 'occupation' => 'Cottage Worker'],
            ['name' => 'Anwar Hossain', 'gender' => 'male', 'occupation' => 'Auto Mechanic'],
            ['name' => 'Jesmin Ara', 'gender' => 'female', 'occupation' => 'Street Food Vendor'],
            ['name' => 'Delwar Hossain', 'gender' => 'male', 'occupation' => 'Mason'],
            ['name' => 'Shahanara Begum', 'gender' => 'female', 'occupation' => 'Poultry Farmer'],
            ['name' => 'Kabir Ahmed', 'gender' => 'male', 'occupation' => 'Small Trader'],
            ['name' => 'Taslima Akter', 'gender' => 'female', 'occupation' => 'Handloom Worker'],
        ];

        $members = [];

        foreach ($defs as $i => $def) {
            $officer = $officers[$i % count($officers)];
            $area = $officer->areas()->first();

            $member = Member::firstOrCreate(['mobile' => '01' . str_pad((string) (810000000 + $i), 9, '0', STR_PAD_LEFT)], [
                'member_no' => $this->numbers->nextMemberNumber(),
                'membership_date' => now()->subMonths(7)->subDays($i * 3),
                'name' => $def['name'],
                'name_bn' => $def['name'],
                'father_husband_name' => 'Md. ' . explode(' ', $def['name'])[1] ?? 'User',
                'mother_name' => 'Amena Begum',
                'dob' => now()->subYears(25 + ($i % 25))->subMonths($i % 12),
                'gender' => $def['gender'],
                'mobile' => '01' . str_pad((string) (810000000 + $i), 9, '0', STR_PAD_LEFT),
                'nid' => (string) (300000000000000 + $i),
                'address' => $area->name . ', Chattogram',
                'area_id' => $area->id,
                'field_officer_id' => $officer->id,
                'occupation' => $def['occupation'],
                'status' => 'active',
                'created_by' => $officer->id,
            ]);

            MemberNominee::firstOrCreate(['member_id' => $member->id, 'name' => 'Son/Daughter of ' . $def['name']], [
                'relationship' => 'Son',
                'nid' => (string) (400000000000000 + $i),
                'mobile' => '01' . str_pad((string) (820000000 + $i), 9, '0', STR_PAD_LEFT),
                'address' => $area->name . ', Chattogram',
                'percentage' => 100,
                'priority' => 1,
            ]);

            $members[] = $member;
        }

        return $members;
    }

    protected function seedSavings(array $members, array $officers, $areas): void
    {
        $programs = SavingsProgram::all()->keyBy('code');

        foreach ($members as $i => $member) {
            $officer = $member->fieldOfficer;

            $daily = $this->openSavingsAccount($member, $programs['DS'], $officer);
            $this->postSavingsDeposits($daily, 'daily', 12);

            if ($i % 2 === 0) {
                $weekly = $this->openSavingsAccount($member, $programs['WS'], $officer);
                $this->postSavingsDeposits($weekly, 'weekly', 4);
            }

            if ($i % 3 === 0) {
                $monthly = $this->openSavingsAccount($member, $programs['MS'], $officer);
                $this->postSavingsDeposits($monthly, 'monthly', 3);
            }
        }
    }

    protected function openSavingsAccount(Member $member, SavingsProgram $program, User $officer): SavingsAccount
    {
        $openingDate = match ($program->frequency) {
            'daily' => now()->subDays(20),
            'weekly' => now()->subDays(28),
            default => now()->subDays(90),
        };

        $account = SavingsAccount::firstOrCreate(
            ['member_id' => $member->id, 'savings_program_id' => $program->id],
            [
                'account_no' => $this->numbers->nextAccountNumber($program->prefix, $member->area?->code),
                'area_id' => $member->area_id,
                'field_officer_id' => $officer->id,
                'opening_date' => $openingDate,
                'opening_balance' => 0,
                'current_balance' => 0,
                'min_deposit' => $program->min_deposit,
                'expected_deposit' => $program->expected_deposit,
                'status' => 'active',
                'created_by' => $officer->id,
            ]
        );

        if (! $account->transactions()->where('type', 'account_opening')->exists()) {
            $this->savings->accountOpening($account, $this->depositPayload($program->expected_deposit, $openingDate, $officer));
        }

        return $account;
    }

    protected function postSavingsDeposits(SavingsAccount $account, string $frequency, int $count): void
    {
        if ($account->transactions()->where('type', 'deposit')->exists()) {
            return;
        }

        $dates = $this->collectionDates($frequency, $count);
        $amount = (float) $account->expected_deposit;

        foreach ($dates as $date) {
            $this->savings->deposit($account, $this->depositPayload($amount, $date, $account->fieldOfficer));
        }
    }

    protected function depositPayload(float $amount, Carbon $date, User $officer): array
    {
        return [
            'amount' => $amount,
            'txn_date' => $date->toDateString(),
            'collection_date' => $date->toDateString(),
            'payment_method' => 'cash',
            'field_officer_id' => $officer->id,
            'area_id' => $officer->areas()->first()?->id,
            'notes' => 'Demo collection',
        ];
    }

    protected function collectionDates(string $frequency, int $count): array
    {
        $dates = [];
        $cursor = now()->startOfDay();

        while (count($dates) < $count) {
            if ($frequency === 'monthly') {
                $dates[] = $cursor->copy();
                $cursor = $cursor->subMonths(1);
                continue;
            }

            if ($frequency === 'weekly') {
                $dates[] = $cursor->copy();
                $cursor = $cursor->subDays(7);
                continue;
            }

            if (! in_array($cursor->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY])) {
                $dates[] = $cursor->copy();
            }
            $cursor = $cursor->subDay();
        }

        return $dates;
    }

    protected function seedLoans(array $members, array $officers, $areas): void
    {
        $products = LoanProduct::all()->keyBy('code');

        $this->createLoan(
            $members[0], $products['DL'], $officers[0],
            20000, 60, 10, now()->subDays(30), now()->subDays(30),
            collect(range(1, 30))
        );

        $this->createLoan(
            $members[1], $products['WL'], $officers[1],
            50000, 26, 20, now()->subWeeks(10), now()->subWeeks(10),
            collect(range(1, 10))
        );

        $this->createLoan(
            $members[2], $products['ML'], $officers[2],
            100000, 12, 20, now()->subMonths(10), now()->subMonths(10),
            collect(range(1, 10))
        );

        $this->createLoan(
            $members[3], $products['ML'], $officers[3],
            60000, 6, 20, now()->subMonths(6), now()->subMonths(6),
            collect(range(1, 6))
        );

        $overdue = $this->createLoan(
            $members[4], $products['WL'], $officers[4],
            30000, 20, 20, now()->subWeeks(6), now()->subWeeks(6),
            collect()
        );

        if ($overdue && $overdue->schedules()->exists() && ! \App\Models\LoanRepayment::where('loan_id', $overdue->id)->exists()) {
            $installment = (float) $overdue->installment_amount;
            $this->repay->collect($overdue, [
                'amount' => round($installment * 0.3, 2),
                'collection_date' => $overdue->schedules()->first()->due_date->toDateString(),
                'payment_method' => 'cash',
                'field_officer_id' => $overdue->field_officer_id,
                'area_id' => $overdue->area_id,
                'notes' => 'Partial demo repayment',
            ]);
        }

        $this->createLoan(
            $members[5], $products['ML'], $officers[0],
            80000, 12, 20, now()->subDays(3), now()->subDays(3),
            collect()
        );

        $this->createLoan(
            $members[6], $products['DL'], $officers[1],
            25000, 60, 10, now()->subDays(5), now()->subDays(5),
            collect()
        );

        $this->createLoan(
            $members[7], $products['ML'], $officers[2],
            50000, 12, 20, now()->subDays(9), now()->subDays(9),
            collect()
        );
    }

    protected function createLoan(
        Member $member,
        LoanProduct $product,
        User $officer,
        float $amount,
        int $term,
        float $rate,
        Carbon $disbursementDate,
        Carbon $firstDueDate,
        $paidInstallments
    ): ?Loan {
        $application = $this->createApplication(
            $member, $product, $officer, $amount, $term, 'Business loan',
            $disbursementDate, null
        );

        if ($application->loan) {
            return $application->loan;
        }

        $loan = $this->disburse->disburse($application, [
            'amount' => $amount,
            'interest_rate' => $rate,
            'term' => $term,
            'disbursement_date' => $disbursementDate->toDateString(),
            'first_due_date' => $firstDueDate->toDateString(),
            'payment_method' => 'cash',
            'notes' => 'Demo loan disbursement',
        ]);

        if ($paidInstallments->isEmpty()) {
            return $loan;
        }

        $installment = (float) $loan->installment_amount;
        $schedules = $loan->schedules()->orderBy('installment_no')->get();

        foreach ($paidInstallments as $index) {
            $schedule = $schedules[$index - 1] ?? null;
            if (! $schedule) {
                break;
            }

            try {
                $this->repay->collect($loan, [
                    'amount' => round($installment, 2),
                    'collection_date' => $schedule->due_date->toDateString(),
                    'payment_method' => 'cash',
                    'field_officer_id' => $loan->field_officer_id,
                    'area_id' => $loan->area_id,
                    'notes' => 'Demo repayment installment ' . $index,
                ]);
            } catch (\RuntimeException $e) {
                $this->command->warn("  Loan {$loan->loan_no}: {$e->getMessage()}");
                break;
            }
        }

        return $loan;
    }

    protected function createApplication(
        Member $member,
        LoanProduct $product,
        User $officer,
        float $amount,
        int $term,
        string $purpose,
        Carbon $applicationDate,
        ?User $manager
    ): LoanApplication {
        $application = LoanApplication::firstOrCreate(
            [
                'member_id' => $member->id,
                'loan_product_id' => $product->id,
                'requested_amount' => $amount,
                'requested_term' => $term,
            ],
            [
                'application_no' => $this->numbers->nextTransactionNumber('loan_application', 'LA-', true, $applicationDate->toDateString()),
                'purpose' => $purpose,
                'area_id' => $member->area_id,
                'field_officer_id' => $officer->id,
                'application_date' => $applicationDate->toDateString(),
                'status' => 'approved',
                'created_by' => $officer->id,
                'approved_amount' => $amount,
                'approved_interest_rate' => $product->interest_rate,
                'approved_term' => $term,
                'approved_installment' => app(LoanScheduleService::class)->calculate(
                    new Loan(['interest_type' => $product->interest_type, 'frequency' => $product->frequency]),
                    $amount, $term, $product->interest_rate
                )[2],
                'approved_by' => $manager?->id,
                'approved_at' => now(),
            ]
        );

        return $application;
    }

    protected function seedCashBook(User $manager): void
    {
        $expenseCategories = [
            'Office Rent', 'Utilities', 'Stationery', 'Transport',
        ];
        $expenseCatIds = [];
        foreach ($expenseCategories as $name) {
            $expenseCatIds[$name] = ExpenseCategory::firstOrCreate(['name' => $name], ['status' => 'active'])->id;
        }

        $expenses = [
            ['category' => 'Office Rent', 'amount' => 5000, 'days_ago' => 1, 'payee' => 'Landlord'],
            ['category' => 'Utilities', 'amount' => 1200, 'days_ago' => 2, 'payee' => 'DPDC'],
            ['category' => 'Stationery', 'amount' => 850, 'days_ago' => 4, 'payee' => 'Stationery Shop'],
            ['category' => 'Transport', 'amount' => 600, 'days_ago' => 6, 'payee' => 'CNG Bill'],
        ];

        foreach ($expenses as $def) {
            $date = now()->subDays($def['days_ago']);
            $no = $this->numbers->nextTransactionNumber('expense', 'EXP-', true, $date->toDateString());

            $expense = Expense::firstOrCreate(['expense_date' => $date->toDateString(), 'expense_category_id' => $expenseCatIds[$def['category']], 'amount' => $def['amount']], [
                'expense_no' => $no,
                'payment_method' => 'cash',
                'payee' => $def['payee'],
                'description' => 'Demo expense',
                'created_by' => $manager->id,
                'status' => 'posted',
            ]);

            if ($expense->wasRecentlyCreated) {
                $this->cash->record(
                    register: $this->cash->openRegister($date->toDateString()),
                    type: 'expense',
                    direction: 'out',
                    amount: (float) $def['amount'],
                    source: $expense,
                    paymentMethod: 'cash',
                    reference: $no,
                    notes: $def['category'],
                );
            }
        }

        $incomeCategories = [
            'Membership Fee', 'Service Charge', 'Miscellaneous',
        ];
        $incomeCatIds = [];
        foreach ($incomeCategories as $name) {
            $incomeCatIds[$name] = IncomeCategory::firstOrCreate(['name' => $name], ['status' => 'active'])->id;
        }

        $incomes = [
            ['category' => 'Membership Fee', 'amount' => 1500, 'days_ago' => 1, 'source' => 'New members'],
            ['category' => 'Service Charge', 'amount' => 1250, 'days_ago' => 3, 'source' => 'Loan processing'],
            ['category' => 'Miscellaneous', 'amount' => 800, 'days_ago' => 5, 'source' => 'Donation'],
        ];

        foreach ($incomes as $def) {
            $date = now()->subDays($def['days_ago']);
            $no = $this->numbers->nextTransactionNumber('income', 'INC-', true, $date->toDateString());

            $income = Income::firstOrCreate(['income_date' => $date->toDateString(), 'income_category_id' => $incomeCatIds[$def['category']], 'amount' => $def['amount']], [
                'income_no' => $no,
                'payment_method' => 'cash',
                'source' => $def['source'],
                'description' => 'Demo income',
                'created_by' => $manager->id,
                'status' => 'posted',
            ]);

            if ($income->wasRecentlyCreated) {
                $this->cash->record(
                    register: $this->cash->openRegister($date->toDateString()),
                    type: 'other_income',
                    direction: 'in',
                    amount: (float) $def['amount'],
                    source: $income,
                    paymentMethod: 'cash',
                    reference: $no,
                    notes: $def['category'],
                );
            }
        }
    }

    protected function seedWithdrawals(array $members): void
    {
        $dailyAccount = $members[0]->savingsAccounts()->whereHas('program', fn ($q) => $q->where('code', 'DS'))->first();
        if ($dailyAccount && $dailyAccount->current_balance > 0) {
            $this->makeWithdrawal($dailyAccount, 2000);
        }

        $weeklyAccount = $members[4]->savingsAccounts()->whereHas('program', fn ($q) => $q->where('code', 'WS'))->first();
        if ($weeklyAccount && $weeklyAccount->current_balance > 0) {
            $this->makeWithdrawal($weeklyAccount, 2000);
        }

        $weeklyAccount2 = $members[2]->savingsAccounts()->whereHas('program', fn ($q) => $q->where('code', 'WS'))->first();
        if ($weeklyAccount2 && $weeklyAccount2->current_balance > 0) {
            $this->makeWithdrawal($weeklyAccount2, 1500);
        }
    }

    protected function makeWithdrawal(SavingsAccount $account, float $amount): void
    {
        $this->savings->withdraw($account, [
            'amount' => $amount,
            'txn_date' => now()->subDay()->toDateString(),
            'collection_date' => now()->subDay()->toDateString(),
            'payment_method' => 'cash',
            'field_officer_id' => $account->field_officer_id,
            'area_id' => $account->area_id,
            'notes' => 'Withdrawal (demo data)',
        ]);
    }

    protected function seedSettlements(array $officers, User $manager): void
    {
        foreach ([$officers[0], $officers[1]] as $officer) {
            $date = now()->toDateString();
            $totals = $this->settlements->computeTotals($officer, $date);

            if ($totals['total_collection'] <= 0) {
                continue;
            }

            $settlementNo = $this->numbers->nextTransactionNumber('settlement', 'STL-', true, $date);

            $settlement = \App\Models\FieldOfficerSettlement::firstOrCreate(['field_officer_id' => $officer->id, 'settlement_date' => $date], [
                'settlement_no' => $settlementNo,
                'savings_collection' => $totals['savings_collection'],
                'loan_collection' => $totals['loan_collection'],
                'other_collection' => $totals['other_collection'],
                'total_collection' => $totals['total_collection'],
                'cash_submitted' => $totals['total_collection'],
                'remaining_cash' => 0,
                'status' => 'submitted',
                'notes' => 'Demo daily settlement',
            ]);

            if ($settlement->wasRecentlyCreated) {
                $this->settlements->receive($settlement);
                $this->command->info("  Settlement {$settlementNo} received for {$officer->name} (৳" . number_format($totals['total_collection'], 2) . ')');
            }
        }
    }
}