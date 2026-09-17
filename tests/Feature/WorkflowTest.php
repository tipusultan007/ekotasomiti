<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\BankAccount;

use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\LoanSchedule;
use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\SavingsProgram;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_admin_can_login_and_see_dashboard(): void
    {
        $response = $this->post('/login', ['email' => 'admin@ekota.com', 'password' => 'password']);
        $response->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertOk();
    }

    public function test_member_savings_and_withdrawal_flow(): void
    {
        $admin = User::where('email', 'admin@ekota.com')->first();
        $this->actingAs($admin);

        $area = Area::first();
        $officer = $this->officerUser();
        if (! $officer) {
            $officer = FieldOfficer::create([
                'code' => 'FO-001', 'name' => 'Test Officer', 'mobile' => '01700000000',
                'joining_date' => now()->toDateString(), 'status' => 'active',
            ]);
            $officer->areas()->attach($area->id);
        }

        $memberResponse = $this->post('/members', [
            'member_no' => 'M-0001',
            'name' => 'Test Member',
            'mobile' => '01811111111',
            'nid' => '1234567890',
            'area_id' => $area->id,
            'field_officer_id' => $officer->id,
            'membership_date' => now()->toDateString(),
            'gender' => 'male',
            'occupation' => 'Service',
            'address' => 'Dhaka',
            'status' => 'active',
            'nominees' => [],
        ]);
        $memberResponse->assertSessionHasNoErrors();
        $memberResponse->assertRedirect(route('members.show', Member::orderByDesc('id')->first()));

        $member = Member::orderByDesc('id')->first();
        $this->assertNotNull($member);

        $program = SavingsProgram::first();
        $accountResponse = $this->post('/savings/accounts', [
            'member_id' => $member->id,
            'savings_program_id' => $program->id,
            'area_id' => $area->id,
            'field_officer_id' => $officer->id,
            'opening_date' => now()->toDateString(),
            'opening_balance' => 0,
            'min_deposit' => $program->min_deposit,
            'expected_deposit' => $program->expected_deposit,
            'status' => 'active',
        ]);
        $accountResponse->assertSessionHasNoErrors();
        $accountResponse->assertRedirect(route('savings.accounts.index'));

        $account = $member->savingsAccounts()->first();
        $this->assertNotNull($account);
        $this->assertEquals('active', $account->status);

        $deposit = $this->post('/savings/deposits', [
            'account_id' => $account->id,
            'amount' => 1000,
            'txn_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'notes' => 'First deposit',
        ]);
        $deposit->assertSessionHasNoErrors();

        $this->assertSame('1000.00', (string) $account->refresh()->current_balance);

        $withdrawal = $this->post('/savings/withdrawals', [
            'savings_account_id' => $account->id,
            'amount' => 300,
            'payment_method' => 'cash',
            'purpose' => 'Emergency',
        ]);
        $withdrawal->assertSessionHasNoErrors();

        $this->assertSame('700.00', (string) $account->refresh()->current_balance);

        $withdrawalTxn = $account->transactions()->where('type', 'withdrawal')->first();
        $this->assertNotNull($withdrawalTxn, 'Withdrawal transaction not created after submission');
        $this->post(route('savings.transactions.reverse', $withdrawalTxn))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('savings_transactions', ['id' => $withdrawalTxn->id]);
        $this->assertSame('1000.00', (string) $account->refresh()->current_balance);

        $cashTxn = \App\Models\CashTransaction::where('source_type', \App\Models\SavingsTransaction::class)
            ->where('source_id', $withdrawalTxn->id)->first();
        $this->assertNull($cashTxn, 'Linked cash transaction should be deleted');
    }

    public function test_savings_account_opening_and_closing_transaction_types(): void
    {
        $admin = User::where('email', 'admin@ekota.com')->first();
        $this->actingAs($admin);

        $area = Area::first();
        $officer = $this->officerUser();
        if (! $officer) {
            $officer = FieldOfficer::create([
                'code' => 'FO-002', 'name' => 'Opening Officer', 'mobile' => '01700000001',
                'joining_date' => now()->toDateString(), 'status' => 'active',
            ]);
            $officer->areas()->attach($area->id);
        }

        $memberResponse = $this->post('/members', [
            'member_no' => 'M-OPN1',
            'name' => 'Opening Member',
            'mobile' => '01811111112',
            'nid' => '9876543210',
            'area_id' => $area->id,
            'field_officer_id' => $officer->id,
            'membership_date' => now()->toDateString(),
            'gender' => 'female',
            'occupation' => 'Business',
            'address' => 'Khulna',
            'status' => 'active',
            'nominees' => [],
        ]);
        $memberResponse->assertSessionHasNoErrors();

        $member = Member::orderByDesc('id')->first();
        $this->assertNotNull($member);

        $program = SavingsProgram::first();

        $open = $this->post('/savings/accounts', [
            'member_id' => $member->id,
            'savings_program_id' => $program->id,
            'area_id' => $area->id,
            'field_officer_id' => $officer->id,
            'opening_date' => now()->toDateString(),
            'opening_balance' => 10000,
            'min_deposit' => $program->min_deposit,
            'expected_deposit' => $program->expected_deposit,
            'status' => 'active',
        ]);
        $open->assertSessionHasNoErrors();

        $account = $member->savingsAccounts()->first();
        $this->assertNotNull($account);
        $this->assertSame('10000.00', (string) $account->refresh()->current_balance);

        $opening = $account->transactions()->where('type', 'account_opening')->first();
        $this->assertNotNull($opening, 'Account opening transaction not created');
        $this->assertSame('posted', $opening->status);

        $close = $this->post(route('savings.accounts.close', $account), [
            'closing_reason' => 'Member left the society',
            'close_date' => now()->toDateString(),
        ]);
        $close->assertSessionHasNoErrors();
        $close->assertRedirect(route('savings.accounts.index'));

        $account->refresh();
        $this->assertSame('closed', $account->status);
        $this->assertSame('0.00', (string) $account->current_balance);

        $closing = $account->transactions()->where('type', 'account_closing')->first();
        $this->assertNotNull($closing, 'Account closing transaction not created');
        $this->assertSame('posted', $closing->status);
    }

    public function test_collection_pages_record_savings_and_loan_installments(): void
    {
        $admin = User::where('email', 'admin@ekota.com')->first();
        $this->actingAs($admin);

        $area = Area::first();
        $officer = $this->officerUser();
        if (! $officer) {
            $officer = FieldOfficer::create([
                'code' => 'FO-CL', 'name' => 'Collection Officer', 'mobile' => '01977777777',
                'joining_date' => now()->toDateString(), 'status' => 'active',
            ]);
            $officer->areas()->attach($area->id);
        }

        $member = Member::create([
            'member_no' => 'M-CL-1', 'name' => 'Collection Member', 'mobile' => '01988888888',
            'nid' => '7777777777', 'area_id' => $area->id, 'field_officer_id' => $officer->id,
            'membership_date' => now()->toDateString(), 'gender' => 'male',
            'occupation' => 'Service', 'address' => 'Rajshahi', 'status' => 'active',
        ]);

        $program = SavingsProgram::where('frequency', 'daily')->first() ?? SavingsProgram::first();
        $frequency = $program->frequency;

        $account = SavingsAccount::create([
            'member_id' => $member->id, 'savings_program_id' => $program->id,
            'area_id' => $area->id, 'field_officer_id' => $officer->id,
            'account_no' => 'SAV-CL-001', 'opening_date' => now()->toDateString(),
            'opening_balance' => 0, 'current_balance' => 0,
            'min_deposit' => $program->min_deposit, 'expected_deposit' => $program->expected_deposit,
            'status' => 'active',
        ]);

        $savingsResponse = $this->post(route('collection.savings.store', $frequency), [
            'account_id' => $account->id,
            'amount' => 500,
            'txn_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);
        $savingsResponse->assertSessionHasNoErrors();
        $savingsResponse->assertRedirect();
        $this->assertSame('500.00', (string) $account->refresh()->current_balance);

        $this->get(route('collection.savings.sheet', $frequency).'?account_id='.$account->id)->assertOk();
        $this->get(route('collection.savings.sheet', $frequency).'?account_id=99999')->assertOk();
        $this->get(route('collection.savings.deposit', $frequency))->assertOk();
        $this->get(route('collection.savings.details', $frequency).'?account_id='.$account->id)->assertOk();

        $product = LoanProduct::first();
        $loan = Loan::create([
            'loan_no' => 'L-CL-001', 'member_id' => $member->id,
            'loan_product_id' => $product->id, 'area_id' => $area->id,
            'field_officer_id' => $officer->id, 'principal_amount' => 1000,
            'interest_rate' => $product->interest_rate, 'interest_type' => $product->interest_type,
            'term' => $product->min_term, 'frequency' => 'daily',
            'installment_amount' => 1000, 'processing_fee' => 0, 'insurance_fee' => 0,
            'total_interest' => 0, 'total_payable' => 1000, 'total_paid' => 0, 'outstanding' => 1000,
            'disbursement_date' => now()->toDateString(),
            'first_due_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        LoanSchedule::create([
            'loan_id' => $loan->id, 'installment_no' => 1,
            'due_date' => now()->toDateString(),
            'principal' => 1000, 'interest' => 0, 'total' => 1000,
            'paid' => 0, 'late_fee' => 0, 'status' => 'due',
        ]);

        $loanResponse = $this->post(route('collection.loans.store', 'daily'), [
            'loan_id' => $loan->id,
            'amount' => 1000,
            'collection_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);
        $loanResponse->assertSessionHasNoErrors();
        $loanResponse->assertRedirect();
        $this->assertSame('0.00', (string) $loan->refresh()->outstanding);
        $this->assertSame('paid', $loan->schedules()->first()->refresh()->status);

        $this->get(route('collection.loans.sheet', 'daily').'?loan_id='.$loan->id)->assertOk();
        $this->get(route('collection.loans.sheet', 'daily').'?loan_id=99999')->assertOk();
        $this->get(route('collection.loans.repay', 'daily'))->assertOk();
        $this->get(route('collection.loans.details', 'daily').'?loan_id='.$loan->id)->assertOk();
    }

    public function test_loan_application_to_repayment_flow(): void
    {
        $admin = User::where('email', 'admin@ekota.com')->first();
        $this->actingAs($admin);

        $area = Area::first();
        $officer = $this->officerUser();
        if (! $officer) {
            $officer = FieldOfficer::create([
                'code' => 'FO-002', 'name' => 'Test Officer 2', 'mobile' => '01900000000',
                'joining_date' => now()->toDateString(), 'status' => 'active',
            ]);
            $officer->areas()->attach($area->id);
        }

        $member = Member::create([
            'member_no' => 'M-0002', 'name' => 'Loan Member', 'mobile' => '01922222222',
            'nid' => '9876543210', 'area_id' => $area->id, 'field_officer_id' => $officer->id,
            'membership_date' => now()->toDateString(), 'gender' => 'female',
            'occupation' => 'Business', 'address' => 'Chattogram', 'status' => 'active',
        ]);

        $product = LoanProduct::first();

        $application = $this->post('/loans/applications', [
            'member_id' => $member->id,
            'loan_product_id' => $product->id,
            'area_id' => $area->id,
            'field_officer_id' => $officer->id,
            'requested_amount' => 50000,
            'requested_term' => $product->min_term,
            'application_date' => now()->toDateString(),
            'disbursement_date' => now()->toDateString(),
            'first_due_date' => now()->addMonth()->toDateString(),
            'payment_method' => 'cash',
            'guarantor_name' => 'G1',
            'guarantor_mobile' => '01711111111',
            'guarantor_nid' => '5555555555',
            'purpose' => 'Business',
        ]);
        $application->assertSessionHasNoErrors();
        $this->assertSame(302, $application->getStatusCode(), 'Store should redirect after success. Location: ' . ($application->headers->get('Location') ?? 'none'));

        $loanId = (int) basename(parse_url($application->headers->get('Location'), PHP_URL_PATH));
        $loan = \App\Models\Loan::find($loanId);
        $this->assertNotNull($loan, 'Loan not created automatically after application');
        $this->assertEquals($product->min_term, $loan->term);
        $this->assertSame((int) $product->min_term, $loan->schedules()->count());

        $app = $loan->application;
        $this->assertNotNull($app);
        $this->assertSame('disbursed', $app->status);

        $collect = $this->post("/loans/{$loan->id}/repay", [
            'amount' => (float) $loan->installment_amount,
            'collection_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'field_officer_id' => $officer->id,
        ]);
        $collect->assertSessionHasNoErrors();

        $this->assertGreaterThan(0, (float) $loan->refresh()->total_paid);

        $this->get("/loans/{$loan->id}/schedule")->assertOk();
        $this->get("/loans/{$loan->id}/collection-sheet")->assertOk();
        $this->get('/loans/overdue')->assertOk();
    }

    public function test_overdue_status_refreshes_on_loan_list_load(): void
    {
        $admin = User::where('email', 'admin@ekota.com')->first();
        $this->actingAs($admin);

        $area = Area::first();
        $officer = $this->officerUser();
        if (! $officer) {
            $officer = FieldOfficer::create([
                'code' => 'FO-OVD', 'name' => 'Overdue Officer', 'mobile' => '01933333333',
                'joining_date' => now()->toDateString(), 'status' => 'active',
            ]);
            $officer->areas()->attach($area->id);
        }

        $member = Member::create([
            'member_no' => 'M-OVD-1', 'name' => 'Overdue Member', 'mobile' => '01944444444',
            'nid' => '5555555555', 'area_id' => $area->id, 'field_officer_id' => $officer->id,
            'membership_date' => now()->toDateString(), 'gender' => 'male',
            'occupation' => 'Farmer', 'address' => 'Cox\'s Bazar', 'status' => 'active',
        ]);

        $product = LoanProduct::first();

        $loan = Loan::create([
            'loan_no' => 'L-OVD-001', 'member_id' => $member->id,
            'loan_product_id' => $product->id, 'area_id' => $area->id,
            'field_officer_id' => $officer->id, 'principal_amount' => 1000,
            'interest_rate' => $product->interest_rate, 'interest_type' => $product->interest_type,
            'term' => $product->min_term, 'frequency' => $product->frequency,
            'installment_amount' => 1000, 'processing_fee' => 0, 'insurance_fee' => 0,
            'total_interest' => 0, 'total_payable' => 1000, 'total_paid' => 0, 'outstanding' => 1000,
            'disbursement_date' => now()->subMonth()->toDateString(),
            'first_due_date' => now()->subMonth()->toDateString(),
            'status' => 'active',
        ]);

        LoanSchedule::create([
            'loan_id' => $loan->id, 'installment_no' => 1,
            'due_date' => now()->subDay()->toDateString(),
            'principal' => 1000, 'interest' => 0, 'total' => 1000,
            'paid' => 0, 'late_fee' => 0, 'status' => 'due',
        ]);

        $this->assertSame('active', $loan->refresh()->status);
        $this->get(route('loans.index'))->assertOk();
        $this->assertSame('overdue', $loan->refresh()->status);
    }

    public function test_loan_write_off(): void
    {
        $admin = User::where('email', 'admin@ekota.com')->first();
        $this->actingAs($admin);

        $area = Area::first();
        $officer = $this->officerUser();
        if (! $officer) {
            $officer = FieldOfficer::create([
                'code' => 'FO-WO', 'name' => 'Write Off Officer', 'mobile' => '01955555555',
                'joining_date' => now()->toDateString(), 'status' => 'active',
            ]);
            $officer->areas()->attach($area->id);
        }

        $member = Member::create([
            'member_no' => 'M-WO-1', 'name' => 'Write Off Member', 'mobile' => '01966666666',
            'nid' => '6666666666', 'area_id' => $area->id, 'field_officer_id' => $officer->id,
            'membership_date' => now()->toDateString(), 'gender' => 'female',
            'occupation' => 'Business', 'address' => 'Dhaka', 'status' => 'active',
        ]);

        $product = LoanProduct::first();

        $loan = Loan::create([
            'loan_no' => 'L-WO-001', 'member_id' => $member->id,
            'loan_product_id' => $product->id, 'area_id' => $area->id,
            'field_officer_id' => $officer->id, 'principal_amount' => 6000,
            'interest_rate' => $product->interest_rate, 'interest_type' => $product->interest_type,
            'term' => $product->min_term, 'frequency' => $product->frequency,
            'installment_amount' => 1000, 'processing_fee' => 0, 'insurance_fee' => 0,
            'total_interest' => 0, 'total_payable' => 6000, 'total_paid' => 1000, 'outstanding' => 5000,
            'disbursement_date' => now()->subMonth()->toDateString(),
            'first_due_date' => now()->subMonth()->toDateString(),
            'status' => 'overdue',
        ]);

        $resp = $this->post(route('loans.write-off', $loan), [
            'write_off_amount' => 5000,
            'write_off_reason' => 'Member defaulted',
        ]);
        $resp->assertSessionHasNoErrors();
        $resp->assertRedirect(route('loans.show', $loan));

        $loan->refresh();
        $this->assertSame('written_off', $loan->status);
        $this->assertSame('5000.00', (string) $loan->written_off_amount);
        $this->assertSame('Member defaulted', $loan->write_off_reason);
        $this->assertNotNull($loan->written_off_at);
    }

    public function test_cash_reports_and_audit_pages(): void
    {
        $admin = User::where('email', 'admin@ekota.com')->first();
        $this->actingAs($admin);

        $this->post('/cash/register/open', [
            'register_date' => now()->toDateString(),
            'opening_balance' => 10000,
        ])->assertSessionHasNoErrors();

        $this->post('/cash/register/transactions', [
            'register_date' => now()->toDateString(),
            'type' => 'receive',
            'direction' => 'in',
            'amount' => 500,
            'payment_method' => 'cash',
            'notes' => 'Test',
        ])->assertSessionHasNoErrors();

        $expCat = \App\Models\ExpenseCategory::create(['name' => 'Office', 'status' => 'active']);
        $incCat = \App\Models\IncomeCategory::create(['name' => 'Other', 'status' => 'active']);

        $this->post('/cash/expenses', [
            'expense_date' => now()->toDateString(),
            'expense_category_id' => $expCat->id,
            'amount' => 200,
            'payment_method' => 'cash',
            'payee' => 'Stationary Shop',
            'description' => 'Papers',
        ])->assertSessionHasNoErrors();

        $this->post('/cash/incomes', [
            'income_date' => now()->toDateString(),
            'income_category_id' => $incCat->id,
            'amount' => 300,
            'payment_method' => 'cash',
            'source' => 'Misc',
            'description' => 'Test income',
        ])->assertSessionHasNoErrors();

        $this->post('/cash/register/close', [
            'register_date' => now()->toDateString(),
        ])->assertSessionHasNoErrors();

        $this->get('/cash/register')->assertOk();
        $this->get('/cash/expenses')->assertOk();
        $this->get('/cash/incomes')->assertOk();

        $this->get('/reports')->assertOk();
        $this->get('/reports/members')->assertOk();
        $this->get('/reports/savings')->assertOk();
        $this->get('/reports/loans')->assertOk();
        $this->get('/reports/collections')->assertOk();
        $this->get('/reports/cash')->assertOk();
        $this->get('/reports/areas')->assertOk();
        $this->get('/reports/officers')->assertOk();
        $this->get('/audit')->assertOk();
    }

    public function test_collection_transactions_can_be_edited_and_deleted_with_permissions(): void
    {
        $admin = User::where('email', 'admin@ekota.com')->first();
        $this->actingAs($admin);

        $area = Area::first();
        $officer = $this->officerUser();
        if (! $officer) {
            $officer = FieldOfficer::create([
                'code' => 'FO-EDT', 'name' => 'Edit Officer', 'mobile' => '01999999991',
                'joining_date' => now()->toDateString(), 'status' => 'active',
            ]);
            $officer->areas()->attach($area->id);
        }

        $member = Member::create([
            'member_no' => 'M-EDT-1', 'name' => 'Edit Member', 'mobile' => '01999999992',
            'nid' => '7778889999', 'area_id' => $area->id, 'field_officer_id' => $officer->id,
            'membership_date' => now()->toDateString(), 'gender' => 'male',
            'occupation' => 'Service', 'address' => 'Dhaka', 'status' => 'active',
        ]);

        $program = SavingsProgram::where('frequency', 'daily')->first() ?? SavingsProgram::first();
        $frequency = $program->frequency;

        $account = SavingsAccount::create([
            'member_id' => $member->id, 'savings_program_id' => $program->id,
            'area_id' => $area->id, 'field_officer_id' => $officer->id,
            'account_no' => 'SAV-EDT-001', 'opening_date' => now()->toDateString(),
            'opening_balance' => 0, 'current_balance' => 0,
            'min_deposit' => $program->min_deposit, 'expected_deposit' => $program->expected_deposit,
            'status' => 'active',
        ]);

        // --- Savings: record a collection, then edit and delete it ---
        $this->post(route('collection.savings.store', $frequency), [
            'account_id' => $account->id, 'amount' => 500,
            'txn_date' => now()->toDateString(), 'payment_method' => 'cash',
        ])->assertSessionHasNoErrors();

        $this->assertSame('500.00', (string) $account->refresh()->current_balance);
        $original = $account->transactions()->where('type', 'deposit')->first();
        $this->assertNotNull($original);

        $this->post(route('collection.savings.transactions.update', $original), [
            'amount' => 700,
            'txn_date' => now()->toDateString(),
            'notes' => 'Corrected amount',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame('posted', $original->refresh()->status);
        $this->assertSame('700.00', (string) $original->amount);
        $this->assertSame('700.00', (string) $account->refresh()->current_balance);
        $this->assertSame(1, $account->transactions()->where('type', 'deposit')->count(), 'Edit must not create a new transaction');

        $cashLink = \App\Models\CashTransaction::where('source_type', \App\Models\SavingsTransaction::class)
            ->where('source_id', $original->id)->first();
        $this->assertNotNull($cashLink);
        $this->assertSame('posted', $cashLink->status);
        $this->assertSame('700.00', (string) $cashLink->amount);

        $this->post(route('collection.savings.transactions.destroy', $original))
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseMissing('savings_transactions', ['id' => $original->id]);
        $this->assertDatabaseMissing('cash_transactions', ['source_type' => \App\Models\SavingsTransaction::class, 'source_id' => $original->id]);
        $this->assertSame('0.00', (string) $account->refresh()->current_balance);

        // --- Loans: record a repayment, then edit and delete it ---
        $product = LoanProduct::first();
        $loan = Loan::create([
            'loan_no' => 'L-EDT-001', 'member_id' => $member->id,
            'loan_product_id' => $product->id, 'area_id' => $area->id,
            'field_officer_id' => $officer->id, 'principal_amount' => 1000,
            'interest_rate' => $product->interest_rate, 'interest_type' => $product->interest_type,
            'term' => $product->min_term, 'frequency' => 'daily',
            'installment_amount' => 1000, 'processing_fee' => 0, 'insurance_fee' => 0,
            'total_interest' => 0, 'total_payable' => 1000, 'total_paid' => 0, 'outstanding' => 1000,
            'disbursement_date' => now()->toDateString(),
            'first_due_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        LoanSchedule::create([
            'loan_id' => $loan->id, 'installment_no' => 1,
            'due_date' => now()->toDateString(),
            'principal' => 1000, 'interest' => 0, 'total' => 1000,
            'paid' => 0, 'late_fee' => 0, 'status' => 'due',
        ]);

        $this->post(route('collection.loans.store', 'daily'), [
            'loan_id' => $loan->id, 'amount' => 1000,
            'collection_date' => now()->toDateString(), 'payment_method' => 'cash',
        ])->assertSessionHasNoErrors();
        $this->assertSame('0.00', (string) $loan->refresh()->outstanding);

        $repayment = \App\Models\LoanTransaction::where('loan_id', $loan->id)->where('type', 'repayment')->first();
        $this->assertNotNull($repayment);

        $this->post(route('collection.loans.transactions.update', $repayment), [
            'amount' => 500,
            'collection_date' => now()->toDateString(),
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame('posted', $repayment->refresh()->status);
        $this->assertSame('500.00', (string) $repayment->amount);
        $this->assertSame(1, \App\Models\LoanTransaction::where('loan_id', $loan->id)->where('type', 'repayment')->count(), 'Edit must not create a new transaction');
        $this->assertSame('500.00', (string) $loan->refresh()->outstanding);
        $this->assertSame('500.00', (string) $loan->schedules()->first()->refresh()->paid);

        $this->post(route('collection.loans.transactions.destroy', $repayment))
            ->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseMissing('loan_transactions', ['id' => $repayment->id]);
        $this->assertDatabaseMissing('loan_repayments', ['transaction_id' => $repayment->id]);
        $this->assertSame('1000.00', (string) $loan->refresh()->outstanding);
        $this->assertSame('0.00', (string) $loan->schedules()->first()->refresh()->paid);

        // --- Permission restriction: cashier cannot edit or delete ---
        $this->post(route('collection.savings.store', $frequency), [
            'account_id' => $account->id, 'amount' => 600,
            'txn_date' => now()->toDateString(), 'payment_method' => 'cash',
        ])->assertSessionHasNoErrors();
        $savingsTxn = $account->transactions()->where('type', 'deposit')->first();

        $this->post(route('collection.loans.store', 'daily'), [
            'loan_id' => $loan->id, 'amount' => 500,
            'collection_date' => now()->toDateString(), 'payment_method' => 'cash',
        ])->assertSessionHasNoErrors();
        $loanTxn = \App\Models\LoanTransaction::where('loan_id', $loan->id)->where('type', 'repayment')->first();

        $cashier = User::create([
            'name' => 'Cashier Test', 'phone' => '01999999993',
            'email' => 'cashier-test@ekota.com', 'password' => 'password', 'is_active' => true,
        ]);
        $cashier->assignRole('cashier');

        $this->actingAs($cashier);
        $this->post(route('collection.savings.transactions.update', $savingsTxn), [
            'amount' => 900, 'txn_date' => now()->toDateString(),
        ])->assertForbidden();
        $this->post(route('collection.savings.transactions.destroy', $savingsTxn))->assertForbidden();
        $this->post(route('collection.loans.transactions.update', $loanTxn), [
            'amount' => 900, 'collection_date' => now()->toDateString(),
        ])->assertForbidden();
        $this->post(route('collection.loans.transactions.destroy', $loanTxn))->assertForbidden();
    }

    public function test_other_module_pages_render(): void
    {
        $admin = User::where('email', 'admin@ekota.com')->first();
        $this->actingAs($admin);

        $this->get('/areas')->assertOk();
        $this->get('/field-officers')->assertOk();
        $this->get('/users')->assertOk();
        $this->get('/settings')->assertOk();
        $this->get('/members')->assertOk();
        $this->get('/savings/programs')->assertOk();
        $this->get('/savings/accounts')->assertOk();
        $this->get('/savings/deposits')->assertOk();
        $this->get('/savings/withdrawals')->assertOk();
        $this->get('/loans/products')->assertOk();
        $this->get('/loans/applications')->assertOk();
        $this->get('/loans')->assertOk();
        foreach (['daily', 'weekly', 'monthly'] as $frequency) {
            $this->get("/collection/savings/{$frequency}")->assertOk();
            $this->get("/collection/loans/{$frequency}")->assertOk();
        }
        $this->get('/collection/settlements')->assertOk();
        $this->get('/bank-accounts')->assertOk();
    }
}