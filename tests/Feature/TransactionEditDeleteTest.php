<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CashRegister;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanProduct;
use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\SavingsProgram;
use App\Models\User;
use App\Services\LoanDisbursementService;
use App\Services\LoanRepaymentService;
use App\Services\SavingsTransactionService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionEditDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $officer;
    protected Member $member;
    protected SavingsProgram $savingsProgram;
    protected LoanProduct $loanProduct;
    protected Area $area;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->admin = User::where('email', 'admin@ekota.com')->first();
        $this->officer = User::officers()->first();
        $this->area = Area::first();
        $this->savingsProgram = SavingsProgram::first();
        $this->loanProduct = LoanProduct::first();

        $this->member = Member::create([
            'member_no' => 'MB-TEST-01',
            'name' => 'Jane Doe',
            'mobile' => '01800000000',
            'area_id' => $this->area->id,
            'field_officer_id' => $this->officer->id,
            'status' => 'active',
            'membership_date' => now()->toDateString(),
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_savings_account_index_has_details_link_and_route_redirects(): void
    {
        $account = SavingsAccount::create([
            'account_no' => 'SAV-TEST-01',
            'member_id' => $this->member->id,
            'savings_program_id' => $this->savingsProgram->id,
            'area_id' => $this->area->id,
            'field_officer_id' => $this->officer->id,
            'status' => 'active',
            'opening_date' => now()->toDateString(),
            'current_balance' => 1000,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('savings.accounts.index'));
        $response->assertOk();
        $response->assertSee(route('savings.accounts.transactions', $account));
        $response->assertSee('Savings Details');

        // Test GET /savings/accounts/{account} redirects to /savings/accounts/{account}/transactions
        $redirectResponse = $this->actingAs($this->admin)->get('/savings/accounts/' . $account->id);
        $redirectResponse->assertRedirect(route('savings.accounts.transactions', $account));
    }

    public function test_savings_transaction_can_be_edited_and_deleted(): void
    {
        auth()->login($this->admin);

        $account = SavingsAccount::create([
            'account_no' => 'SAV-TEST-02',
            'member_id' => $this->member->id,
            'savings_program_id' => $this->savingsProgram->id,
            'area_id' => $this->area->id,
            'field_officer_id' => $this->officer->id,
            'status' => 'active',
            'opening_date' => now()->toDateString(),
            'current_balance' => 0,
            'created_by' => $this->admin->id,
        ]);

        $txnService = app(SavingsTransactionService::class);
        $txn = $txnService->deposit($account, [
            'amount' => 500,
            'txn_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'notes' => 'Initial deposit',
            'received_by' => $this->admin->id,
        ]);

        $this->assertEquals(500, (float) $account->fresh()->current_balance);

        // Check page shows edit and delete options
        $viewRes = $this->actingAs($this->admin)->get(route('savings.accounts.transactions', $account));
        $viewRes->assertOk();
        $viewRes->assertSee(route('savings.transactions.update', $txn));
        $viewRes->assertSee(route('savings.transactions.destroy', $txn));

        // Edit transaction amount to 750
        $editRes = $this->actingAs($this->admin)->post(route('savings.transactions.update', $txn), [
            'amount' => 750,
            'txn_date' => now()->toDateString(),
            'notes' => 'Updated deposit notes',
        ]);
        $editRes->assertSessionHas('success');
        $this->assertEquals(750, (float) $account->fresh()->current_balance);
        $this->assertEquals('Updated deposit notes', $txn->fresh()->notes);

        // Delete transaction
        $delRes = $this->actingAs($this->admin)->post(route('savings.transactions.destroy', $txn));
        $delRes->assertSessionHas('success');
        $this->assertEquals(0, (float) $account->fresh()->current_balance);
        $this->assertDatabaseMissing('savings_transactions', ['id' => $txn->id]);
    }

    public function test_loan_transaction_can_be_edited_and_deleted(): void
    {
        auth()->login($this->admin);

        $app = LoanApplication::create([
            'application_no' => 'APP-TEST-' . uniqid(),
            'member_id' => $this->member->id,
            'loan_product_id' => $this->loanProduct->id,
            'requested_amount' => 10000,
            'requested_term' => 10,
            'area_id' => $this->area->id,
            'field_officer_id' => $this->officer->id,
            'application_date' => now()->toDateString(),
            'status' => 'approved',
            'approved_amount' => 10000,
            'interest_rate' => 10,
            'created_by' => $this->admin->id,
        ]);

        $disbService = app(LoanDisbursementService::class);
        $loan = $disbService->disburse($app, [
            'disbursement_date' => now()->toDateString(),
            'first_due_date' => now()->addMonth()->toDateString(),
            'payment_method' => 'cash',
            'notes' => 'Disbursed',
        ]);

        $repayService = app(LoanRepaymentService::class);
        $repayTxn = $repayService->collect($loan, [
            'amount' => 1100,
            'collection_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'notes' => 'First repayment',
        ]);

        $this->assertEquals(1100, (float) $loan->fresh()->total_paid);

        // Check loan show page shows edit and delete options for the repayment transaction
        $showRes = $this->actingAs($this->admin)->get(route('loans.show', $loan));
        $showRes->assertOk();
        $showRes->assertSee(route('loans.transactions.update', $repayTxn));
        $showRes->assertSee(route('loans.transactions.destroy', $repayTxn));

        // Edit repayment amount to 1500
        $editRes = $this->actingAs($this->admin)->post(route('loans.transactions.update', $repayTxn), [
            'amount' => 1500,
            'collection_date' => now()->toDateString(),
            'notes' => 'Updated repayment notes',
        ]);
        $editRes->assertSessionHas('success');
        $this->assertEquals(1500, (float) $loan->fresh()->total_paid);
        $this->assertEquals('Updated repayment notes', $repayTxn->fresh()->notes);

        // Delete repayment
        $delRes = $this->actingAs($this->admin)->post(route('loans.transactions.destroy', $repayTxn));
        $delRes->assertSessionHas('success');
        $this->assertEquals(0, (float) $loan->fresh()->total_paid);
        $this->assertDatabaseMissing('loan_transactions', ['id' => $repayTxn->id]);
    }

    public function test_loan_index_has_loan_account_no_link(): void
    {
        auth()->login($this->admin);

        $app = LoanApplication::create([
            'application_no' => 'APP-TEST-' . uniqid(),
            'member_id' => $this->member->id,
            'loan_product_id' => $this->loanProduct->id,
            'requested_amount' => 10000,
            'requested_term' => 10,
            'area_id' => $this->area->id,
            'field_officer_id' => $this->officer->id,
            'application_date' => now()->toDateString(),
            'status' => 'approved',
            'approved_amount' => 10000,
            'interest_rate' => 10,
            'created_by' => $this->admin->id,
        ]);

        $loan = app(LoanDisbursementService::class)->disburse($app, [
            'disbursement_date' => now()->toDateString(),
            'first_due_date' => now()->addMonth()->toDateString(),
            'payment_method' => 'cash',
            'notes' => 'Disbursed',
        ]);

        $response = $this->actingAs($this->admin)->get(route('loans.index'));
        $response->assertOk();
        $response->assertSee(route('loans.show', $loan));
        $response->assertSee($loan->loan_no);
    }
}
