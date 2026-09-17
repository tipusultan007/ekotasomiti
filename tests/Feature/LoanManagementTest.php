<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\CashRegister;
use App\Models\CashTransaction;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanProduct;
use App\Models\Member;
use App\Models\User;
use App\Services\LoanDisbursementService;
use App\Services\LoanRepaymentService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $officer;
    protected Member $member;
    protected LoanProduct $product;
    protected Area $area;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->admin = User::where('email', 'admin@ekota.com')->first();
        $this->officer = User::officers()->first();
        $this->area = Area::first();
        $this->product = LoanProduct::first();

        $this->member = Member::create([
            'member_no' => 'MB-001',
            'name' => 'Test Member',
            'mobile' => '01799999999',
            'area_id' => $this->area->id,
            'field_officer_id' => $this->officer->id,
            'status' => 'active',
            'membership_date' => now()->toDateString(),
            'created_by' => $this->admin->id,
        ]);
    }

    protected function createLoan(float $amount = 10000, int $term = 10, float $rate = 10): Loan
    {
        auth()->login($this->admin);

        $app = LoanApplication::create([
            'application_no' => 'APP-' . uniqid(),
            'member_id' => $this->member->id,
            'loan_product_id' => $this->product->id,
            'requested_amount' => $amount,
            'requested_term' => $term,
            'area_id' => $this->area->id,
            'field_officer_id' => $this->officer->id,
            'application_date' => now()->toDateString(),
            'status' => 'approved',
            'approved_amount' => $amount,
            'approved_term' => $term,
            'approved_interest_rate' => $rate,
            'created_by' => $this->admin->id,
        ]);

        return app(LoanDisbursementService::class)->disburse($app, [
            'amount' => $amount,
            'interest_rate' => $rate,
            'term' => $term,
            'disbursement_date' => now()->toDateString(),
        ]);
    }

    public function test_loan_application_create_page_renders_with_calculation_summary(): void
    {
        $response = $this->actingAs($this->admin)->get(route('loans.applications.create'));
        $response->assertOk();
        $response->assertSee('id="installment_amount"', false);
        $response->assertSee('id="loan-calculation-summary"', false);
        $response->assertSee('id="calc-total-payable"', false);
        $response->assertSee('id="calc-total-interest"', false);
        $response->assertSee('id="calc-principal"', false);
        $response->assertSee('id="calc-installment"', false);
    }

    public function test_loan_application_accepts_manual_installment_amount(): void
    {
        $response = $this->actingAs($this->admin)->post(route('loans.applications.store'), [
            'member_id' => $this->member->id,
            'loan_product_id' => $this->product->id,
            'requested_amount' => 10000,
            'requested_term' => 10,
            'installment_amount' => 1250, // Manual installment set by user
            'purpose' => 'Shop inventory expansion',
            'area_id' => $this->area->id,
            'field_officer_id' => $this->officer->id,
            'application_date' => now()->toDateString(),
            'disbursement_date' => now()->toDateString(),
            'first_due_date' => now()->addDay()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $response->assertSessionHasNoErrors();

        // 1. Verify LoanApplication stored the manual installment
        $app = LoanApplication::where('member_id', $this->member->id)->latest('id')->first();
        $this->assertNotNull($app);
        $this->assertEquals(1250.00, (float) $app->installment_amount);
        $this->assertEquals(1250.00, (float) $app->approved_installment);

        // 2. Verify Loan has the exact manual installment_amount
        $loan = Loan::where('application_id', $app->id)->first();
        $this->assertNotNull($loan);
        $this->assertEquals(1250.00, (float) $loan->installment_amount);
        $this->assertEquals(12500.00, (float) $loan->total_payable); // 1250 * 10
        $this->assertEquals(2500.00, (float) $loan->total_interest);

        // 3. Verify Schedules each have the manual installment amount
        $schedules = $loan->schedules;
        $this->assertCount(10, $schedules);
        foreach ($schedules as $schedule) {
            $this->assertEquals(1250.00, (float) $schedule->total);
        }

        // 4. Verify loan show page and collection page reflect the manual installment
        $loanResponse = $this->actingAs($this->admin)->get(route('loans.show', $loan));
        $loanResponse->assertOk();
        $loanResponse->assertSee('1,250');

        $sheetResponse = $this->actingAs($this->officer)->get(route('collection.loans.sheet', [
            'frequency' => $loan->frequency,
            'date' => $loan->first_due_date->toDateString(),
        ]));
        $sheetResponse->assertOk();
        $sheetResponse->assertSee('1,250');
    }

    public function test_admin_can_view_loan_edit_page(): void
    {
        $loan = $this->createLoan();

        $response = $this->actingAs($this->admin)->get(route('loans.edit', $loan));

        $response->assertStatus(200);
        $response->assertSee($loan->loan_no);
        $response->assertSee('Edit Loan');
    }

    public function test_admin_can_update_unpaid_loan_and_rebuild_schedule(): void
    {
        $loan = $this->createLoan(10000, 10, 10);
        $this->assertEquals(10, $loan->schedules()->count());

        $response = $this->actingAs($this->admin)->put(route('loans.update', $loan), [
            'area_id' => $this->area->id,
            'field_officer_id' => $this->officer->id,
            'principal_amount' => 20000,
            'interest_rate' => 15,
            'term' => 20,
            'disbursement_date' => now()->toDateString(),
            'first_due_date' => now()->addDay()->toDateString(),
            'status' => 'active',
        ]);

        $response->assertRedirect(route('loans.show', $loan));

        $loan->refresh();
        $this->assertEquals(20000, (float) $loan->principal_amount);
        $this->assertEquals(15, (float) $loan->interest_rate);
        $this->assertEquals(20, $loan->term);
        $this->assertEquals(20, $loan->schedules()->count());
    }

    public function test_admin_can_delete_loan_and_clean_all_related_records(): void
    {
        $loan = $this->createLoan(10000, 10, 10);
        $loanId = $loan->id;
        $app = $loan->application;

        // Record a repayment
        $repayTxn = app(LoanRepaymentService::class)->collect($loan, [
            'amount' => 1100,
            'collection_date' => now()->toDateString(),
            'payment_method' => 'cash',
        ]);

        $this->assertGreaterThan(0, $loan->schedules()->count());
        $this->assertGreaterThan(0, $loan->transactions()->count());
        $this->assertGreaterThan(0, $loan->disbursements()->count());

        $response = $this->actingAs($this->admin)->delete(route('loans.destroy', $loan));

        $response->assertRedirect(route('loans.index'));

        // Loan should be deleted
        $this->assertDatabaseMissing('loans', ['id' => $loanId]);
        $this->assertDatabaseMissing('loan_schedules', ['loan_id' => $loanId]);
        $this->assertDatabaseMissing('loan_transactions', ['loan_id' => $loanId]);
        $this->assertDatabaseMissing('loan_disbursements', ['loan_id' => $loanId]);
        $this->assertDatabaseMissing('loan_repayments', ['loan_id' => $loanId]);

        // Cash transactions for this loan should be deleted
        $this->assertDatabaseMissing('cash_transactions', [
            'source_type' => Loan::class,
            'source_id' => $loanId,
        ]);
        $this->assertDatabaseMissing('cash_transactions', [
            'source_type' => \App\Models\LoanTransaction::class,
            'source_id' => $repayTxn->id,
        ]);

        // Application should be reverted to approved
        if ($app) {
            $this->assertEquals('approved', $app->fresh()->status);
        }
    }

    public function test_field_officer_cannot_delete_loan(): void
    {
        $loan = $this->createLoan();

        $response = $this->actingAs($this->officer)->delete(route('loans.destroy', $loan));

        $response->assertStatus(403);
        $this->assertDatabaseHas('loans', ['id' => $loan->id]);
    }

    public function test_overdue_loans_can_be_filtered(): void
    {
        $loan1 = $this->createLoan(10000, 10, 10);
        $loan1->update(['status' => 'overdue', 'frequency' => 'monthly']);

        $loan2 = $this->createLoan(5000, 5, 10);
        $loan2->update(['status' => 'overdue', 'frequency' => 'weekly']);

        // 1. View all overdue
        $res = $this->actingAs($this->admin)->get(route('loans.overdue'));
        $res->assertOk();
        $res->assertSee($loan1->loan_no);
        $res->assertSee($loan2->loan_no);

        // 2. Filter by search
        $searchRes = $this->actingAs($this->admin)->get(route('loans.overdue', ['search' => $loan1->loan_no]));
        $searchRes->assertOk();
        $searchRes->assertSee($loan1->loan_no);
        $searchRes->assertDontSee($loan2->loan_no);

        // 3. Filter by frequency
        $freqRes = $this->actingAs($this->admin)->get(route('loans.overdue', ['frequency' => 'weekly']));
        $freqRes->assertOk();
        $freqRes->assertSee($loan2->loan_no);
        $freqRes->assertDontSee($loan1->loan_no);

        // 4. Filter by product
        $prodRes = $this->actingAs($this->admin)->get(route('loans.overdue', ['loan_product_id' => $loan1->loan_product_id]));
        $prodRes->assertOk();
        $prodRes->assertSee($loan1->loan_no);
    }
}

