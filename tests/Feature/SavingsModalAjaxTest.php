<?php

namespace Tests\Feature;

use App\Models\Area;

use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\SavingsProgram;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SavingsModalAjaxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    protected function createAccount(User $admin): SavingsAccount
    {
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
        $member = Member::orderByDesc('id')->first();

        $program = SavingsProgram::first();
        $this->post('/savings/accounts', [
            'member_id' => $member->id,
            'savings_program_id' => $program->id,
            'area_id' => $area->id,
            'field_officer_id' => $officer->id,
            'opening_date' => now()->toDateString(),
            'opening_balance' => 0,
            'min_deposit' => $program->min_deposit,
            'expected_deposit' => $program->expected_deposit,
            'status' => 'active',
        ])->assertSessionHasNoErrors();

        return $member->savingsAccounts()->first();
    }

    public function test_deposit_store_returns_json_errors_for_ajax_requests(): void
    {
        $admin = User::where('email', 'admin@ekota.com')->first();
        $account = $this->createAccount($admin);

        $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/savings/deposits', [
                'account_id' => $account->id,
                'payment_method' => 'cash',
                'txn_date' => now()->toDateString(),
            ])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['amount']]);
    }

    public function test_withdraw_store_returns_json_error_when_amount_exceeds_balance(): void
    {
        $admin = User::where('email', 'admin@ekota.com')->first();
        $account = $this->createAccount($admin);

        $this->actingAs($admin)
            ->withHeaders(['Accept' => 'application/json'])
            ->post('/savings/withdrawals', [
                'savings_account_id' => $account->id,
                'amount' => 999999999,
                'payment_method' => 'cash',
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_deposit_store_returns_json_success_with_receipt_url(): void
    {
        $admin = User::where('email', 'admin@ekota.com')->first();
        $account = $this->createAccount($admin);

        DB::beginTransaction();

        try {
            $this->actingAs($admin)
                ->withHeaders(['Accept' => 'application/json'])
                ->post('/savings/deposits', [
                    'account_id' => $account->id,
                    'amount' => 50,
                    'payment_method' => 'cash',
                    'txn_date' => now()->toDateString(),
                ])
                ->assertStatus(200)
                ->assertJson(['success' => true])
                ->assertJsonStructure(['message', 'receipt_url']);
        } finally {
            DB::rollBack();
        }
    }
}