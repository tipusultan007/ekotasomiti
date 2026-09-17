<?php

namespace Tests\Feature;

use App\Models\Area;

use App\Models\LoanProduct;
use App\Models\Member;
use App\Models\SavingsProgram;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_onboarding_create_page_renders(): void
    {
        $admin = User::where('email', 'admin@ekota.com')->first();

        $this->actingAs($admin)
            ->get(route('onboarding.create'))
            ->assertOk()
            ->assertSee('Member Onboarding');
    }

    public function test_onboarding_creates_member_savings_and_loan(): void
    {
        $admin = User::where('email', 'admin@ekota.com')->first();
        $area = Area::first();
        $officer = $this->officerUser();
        $program = SavingsProgram::first();
        $product = LoanProduct::first();

        $response = $this->actingAs($admin)
            ->post(route('onboarding.store'), [
                'member' => [
                    'membership_date' => now()->toDateString(),
                    'name' => 'Onboard Test Member',
                    'gender' => 'male',
                    'mobile' => '01822222222',
                    'nid' => '9876543210',
                    'address' => 'Test Address',
                    'area_id' => $area->id,
                    'field_officer_id' => $officer->id,
                    'occupation' => 'Service',
                    'status' => 'active',
                ],
                'nominees' => [
                    ['name' => 'Nominee One', 'relationship' => 'Wife', 'percentage' => 100],
                ],
                'savings' => [
                    'savings_program_id' => $program->id,
                    'opening_date' => now()->toDateString(),
                    'opening_balance' => 500,
                    'area_id' => $area->id,
                    'field_officer_id' => $officer->id,
                    'status' => 'active',
                ],
                'loan' => [
                    'create_loan' => '1',
                    'loan_product_id' => $product->id,
                    'requested_amount' => 10000,
                    'requested_term' => 12,
                    'application_date' => now()->toDateString(),
                    'disbursement_date' => now()->toDateString(),
                    'first_due_date' => now()->addMonth()->toDateString(),
                    'payment_method' => 'cash',
                ],
            ]);
        $response->assertRedirect();

        $member = Member::where('name', 'Onboard Test Member')->first();
        $this->assertNotNull($member, 'Member was not created');

        $this->assertEquals(1, $member->savingsAccounts()->count(), 'Savings account not created');
        $this->assertEquals(1, $member->loanApplications()->count(), 'Loan application not created');
        $this->assertTrue($member->savingsAccounts()->first()->transactions()->where('type', 'account_opening')->exists(), 'Opening balance txn missing');
        $this->assertSame(1, $member->nominees()->count());
    }

    public function test_onboarding_without_loan_creates_only_member_and_savings(): void
    {
        $admin = User::where('email', 'admin@ekota.com')->first();
        $area = Area::first();
        $officer = $this->officerUser();
        $program = SavingsProgram::first();

        $this->actingAs($admin)
            ->post(route('onboarding.store'), [
                'member' => [
                    'membership_date' => now()->toDateString(),
                    'name' => 'No Loan Member',
                    'gender' => 'female',
                    'area_id' => $area->id,
                    'field_officer_id' => $officer->id,
                    'status' => 'active',
                ],
                'savings' => [
                    'savings_program_id' => $program->id,
                    'opening_date' => now()->toDateString(),
                    'opening_balance' => 0,
                    'status' => 'active',
                ],
                'loan' => ['create_loan' => '0'],
            ])
            ->assertRedirect(route('members.show', Member::orderByDesc('id')->first()));

        $member = Member::where('name', 'No Loan Member')->first();
        $this->assertNotNull($member);
        $this->assertEquals(1, $member->savingsAccounts()->count());
        $this->assertEquals(0, $member->loanApplications()->count());
    }
}