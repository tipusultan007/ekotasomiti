<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CashRegister;
use App\Models\Fund;
use App\Models\FundTransaction;
use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\SavingsProgram;
use App\Models\SavingsTransaction;
use App\Models\User;
use App\Services\SavingsTransactionService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FundManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Area $area;
    protected Member $member;
    protected Fund $fund;
    protected SavingsProgram $monthlyProgram;
    protected SavingsAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->admin = User::where('email', 'admin@ekota.com')->first();
        $this->area = Area::first();
        $this->fund = Fund::where('code', 'WF')->first();
        $this->monthlyProgram = SavingsProgram::where('code', 'MS')->first();

        $this->member = Member::create([
            'member_no' => 'M-FUND-01',
            'name' => 'Fund Test Member',
            'membership_date' => now()->toDateString(),
            'gender' => 'male',
            'mobile' => '01700002222',
            'area_id' => $this->area->id,
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $this->account = SavingsAccount::create([
            'account_no' => 'MS-TEST-0001',
            'member_id' => $this->member->id,
            'savings_program_id' => $this->monthlyProgram->id,
            'area_id' => $this->area->id,
            'opening_date' => now()->toDateString(),
            'opening_balance' => 0,
            'current_balance' => 0,
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_monthly_savings_deposit_splits_into_savings_and_fund(): void
    {
        $response = $this->actingAs($this->admin)->post(route('savings.deposits.store'), [
            'account_id' => $this->account->id,
            'amount' => 3000,
            'txn_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'notes' => 'Monthly installment with fund contribution',
        ]);

        $response->assertSessionHasNoErrors();
        $this->account->refresh();
        $this->fund->refresh();

        // 1. Account gets credited net savings 2,950 Tk
        $this->assertEquals(2950.00, (float) $this->account->current_balance);

        // 2. Fund gets credited 50 Tk
        $this->assertEquals(50.00, (float) $this->fund->current_balance);

        // 3. Savings transaction records gross 3000, fund 50, amount 2950
        $txn = SavingsTransaction::where('savings_account_id', $this->account->id)->first();
        $this->assertNotNull($txn);
        $this->assertEquals(3000.00, (float) $txn->gross_amount);
        $this->assertEquals(50.00, (float) $txn->fund_amount);
        $this->assertEquals(2950.00, (float) $txn->amount);
        $this->assertNotNull($txn->fund_transaction_id);

        // 4. FundTransaction exists and is linked
        $fundTxn = FundTransaction::find($txn->fund_transaction_id);
        $this->assertNotNull($fundTxn);
        $this->assertEquals(50.00, (float) $fundTxn->amount);
        $this->assertEquals('credit', $fundTxn->direction);
        $this->assertEquals('contribution', $fundTxn->type);
        $this->assertEquals($this->member->id, $fundTxn->member_id);
        $this->assertEquals($this->account->id, $fundTxn->savings_account_id);

        // 5. Cash register received gross cash 3,000 Tk
        $register = CashRegister::where('status', 'open')->first();
        $this->assertNotNull($register);
        $this->assertEquals(3000.00, (float) $register->total_in);
    }

    public function test_receipt_displays_itemized_fund_breakdown(): void
    {
        $this->actingAs($this->admin)->post(route('savings.deposits.store'), [
            'account_id' => $this->account->id,
            'amount' => 3000,
            'txn_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $txn = SavingsTransaction::where('savings_account_id', $this->account->id)->first();

        $response = $this->actingAs($this->admin)->get(route('savings.receipts.show', [
            'type' => 'savings',
            'id' => $txn->id,
        ]));

        $response->assertOk();
        $response->assertSee('Total Paid');
        $response->assertSee('3,000.00');
        $response->assertSee('Savings Deposit');
        $response->assertSee('2,950.00');
        $response->assertSee('Welfare Fund');
        $response->assertSee('50.00');
    }

    public function test_reversing_savings_deposit_reverses_fund_contribution(): void
    {
        $this->actingAs($this->admin)->post(route('savings.deposits.store'), [
            'account_id' => $this->account->id,
            'amount' => 3000,
            'txn_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $txn = SavingsTransaction::where('savings_account_id', $this->account->id)->first();
        $this->assertEquals(50.00, (float) $this->fund->refresh()->current_balance);

        // Reverse transaction
        $service = app(SavingsTransactionService::class);
        $service->reverse($txn);

        $this->account->refresh();
        $this->fund->refresh();

        // Account balance back to 0
        $this->assertEquals(0.00, (float) $this->account->current_balance);
        // Fund balance back to 0
        $this->assertEquals(0.00, (float) $this->fund->current_balance);

        // Associated fund transaction reversed
        $fundTxn = FundTransaction::where('savings_transaction_id', $txn->id)->first();
        $this->assertEquals('reversed', $fundTxn->status);
    }

    public function test_fund_disbursement_decrements_balance_and_records_cash_outflow(): void
    {
        // First deposit 3000 to have 50 in fund
        $this->actingAs($this->admin)->post(route('savings.deposits.store'), [
            'account_id' => $this->account->id,
            'amount' => 3000,
            'txn_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $this->assertEquals(50.00, (float) $this->fund->refresh()->current_balance);

        // Disburse 30 Tk from fund for member emergency assistance
        $response = $this->actingAs($this->admin)->post(route('funds.disburse', $this->fund), [
            'amount' => 30,
            'member_id' => $this->member->id,
            'payment_method' => 'cash',
            'txn_date' => now()->toDateString(),
            'notes' => 'Medical assistance grant',
        ]);

        $response->assertSessionHasNoErrors();
        $this->fund->refresh();

        // Fund balance is now 50 - 30 = 20
        $this->assertEquals(20.00, (float) $this->fund->current_balance);

        // Cash register records 30 out
        $register = CashRegister::where('status', 'open')->first();
        $this->assertEquals(30.00, (float) $register->total_out);
    }

    public function test_fund_views_are_accessible(): void
    {
        $response = $this->actingAs($this->admin)->get(route('funds.index'));
        $response->assertOk();
        $response->assertSee('Welfare Fund');
        $response->assertSee('WF');

        $showResponse = $this->actingAs($this->admin)->get(route('funds.show', $this->fund));
        $showResponse->assertOk();
        $showResponse->assertSee('Welfare Fund');

        $disburseFormResponse = $this->actingAs($this->admin)->get(route('funds.disburse-form'));
        $disburseFormResponse->assertOk();
        $disburseFormResponse->assertSee('Spend / Disburse from Fund');
    }

    public function test_global_fund_disbursement(): void
    {
        // Deposit 3000 to fund (accumulates 50 Tk)
        $this->actingAs($this->admin)->post(route('savings.deposits.store'), [
            'account_id' => $this->account->id,
            'amount' => 3000,
            'txn_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $this->assertEquals(50.00, (float) $this->fund->refresh()->current_balance);

        // Global disburse 40 Tk
        $response = $this->actingAs($this->admin)->post(route('funds.global-disburse'), [
            'fund_id' => $this->fund->id,
            'amount' => 40,
            'member_id' => $this->member->id,
            'payment_method' => 'cash',
            'txn_date' => now()->toDateString(),
            'notes' => 'Global welfare grant',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('funds.show', $this->fund));

        $this->fund->refresh();
        $this->assertEquals(10.00, (float) $this->fund->current_balance);

        $txn = FundTransaction::where('fund_id', $this->fund->id)->where('type', 'disbursement')->first();
        $this->assertNotNull($txn);
        $this->assertEquals(40.00, (float) $txn->amount);
        $this->assertEquals('debit', $txn->direction);
        $this->assertEquals($this->member->id, $txn->member_id);
    }

    public function test_monthly_savings_proportional_fund_deduction(): void
    {
        // 1. Test 6000 Tk deposit -> 100 Tk to fund, 5900 Tk to savings
        $response1 = $this->actingAs($this->admin)->post(route('savings.deposits.store'), [
            'account_id' => $this->account->id,
            'amount' => 6000,
            'txn_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);
        $response1->assertSessionHasNoErrors();
        $this->account->refresh();
        $this->fund->refresh();

        $this->assertEquals(5900.00, (float) $this->account->current_balance);
        $this->assertEquals(100.00, (float) $this->fund->current_balance);

        $txn1 = SavingsTransaction::where('savings_account_id', $this->account->id)->latest('id')->first();
        $this->assertEquals(6000.00, (float) $txn1->gross_amount);
        $this->assertEquals(100.00, (float) $txn1->fund_amount);
        $this->assertEquals(5900.00, (float) $txn1->amount);

        // 2. Test 9000 Tk deposit -> 150 Tk to fund, 8850 Tk to savings
        // Create second account to test isolation
        $account2 = SavingsAccount::create([
            'account_no' => 'MS-TEST-0002',
            'member_id' => $this->member->id,
            'savings_program_id' => $this->monthlyProgram->id,
            'area_id' => $this->area->id,
            'opening_date' => now()->toDateString(),
            'opening_balance' => 0,
            'current_balance' => 0,
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $response2 = $this->actingAs($this->admin)->post(route('savings.deposits.store'), [
            'account_id' => $account2->id,
            'amount' => 9000,
            'txn_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);
        $response2->assertSessionHasNoErrors();
        $account2->refresh();
        $this->fund->refresh();

        $this->assertEquals(8850.00, (float) $account2->current_balance);
        // Fund balance was 100 + 150 = 250
        $this->assertEquals(250.00, (float) $this->fund->current_balance);

        $txn2 = SavingsTransaction::where('savings_account_id', $account2->id)->latest('id')->first();
        $this->assertEquals(9000.00, (float) $txn2->gross_amount);
        $this->assertEquals(150.00, (float) $txn2->fund_amount);
        $this->assertEquals(8850.00, (float) $txn2->amount);
    }

    public function test_non_monthly_savings_does_not_deduct_fund(): void
    {
        $dailyProgram = SavingsProgram::where('code', 'DS')->first();
        $dailyAccount = SavingsAccount::create([
            'account_no' => 'DS-TEST-0001',
            'member_id' => $this->member->id,
            'savings_program_id' => $dailyProgram->id,
            'area_id' => $this->area->id,
            'opening_date' => now()->toDateString(),
            'opening_balance' => 0,
            'current_balance' => 0,
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $fundBalanceBefore = (float) $this->fund->refresh()->current_balance;

        // Deposit 3000 into daily savings
        $response = $this->actingAs($this->admin)->post(route('savings.deposits.store'), [
            'account_id' => $dailyAccount->id,
            'amount' => 3000,
            'txn_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);
        $response->assertSessionHasNoErrors();
        $dailyAccount->refresh();
        $this->fund->refresh();

        // 3000 goes 100% to savings, 0 to fund
        $this->assertEquals(3000.00, (float) $dailyAccount->current_balance);
        $this->assertEquals($fundBalanceBefore, (float) $this->fund->current_balance);

        $txn = SavingsTransaction::where('savings_account_id', $dailyAccount->id)->first();
        $this->assertEquals(3000.00, (float) $txn->gross_amount);
        $this->assertEquals(0.00, (float) $txn->fund_amount);
        $this->assertEquals(3000.00, (float) $txn->amount);
        $this->assertNull($txn->fund_transaction_id);
    }
}

