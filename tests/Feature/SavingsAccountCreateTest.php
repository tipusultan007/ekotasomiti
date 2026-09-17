<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Member;
use App\Models\SavingsAccount;
use App\Models\SavingsProgram;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavingsAccountCreateTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Area $area;
    protected Member $member;
    protected SavingsProgram $program;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->admin = User::where('email', 'admin@ekota.com')->first();
        $this->area = Area::first();
        $this->program = SavingsProgram::first();

        $this->member = Member::create([
            'member_no' => 'M-TEST-01',
            'name' => 'Savings Test Member',
            'membership_date' => now()->toDateString(),
            'gender' => 'male',
            'mobile' => '01700001111',
            'area_id' => $this->area->id,
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_savings_account_create_page_renders_with_hidden_active_status(): void
    {
        $response = $this->actingAs($this->admin)->get(route('savings.accounts.create'));
        $response->assertOk();
        $response->assertSee('name="status" value="active"', false);
    }

    public function test_savings_account_can_be_created_without_status_and_is_auto_active(): void
    {
        // Notice: no 'status' passed in payload, exactly as reported by the user
        $response = $this->actingAs($this->admin)->post(route('savings.accounts.store'), [
            'member_id' => $this->member->id,
            'savings_program_id' => $this->program->id,
            'area_id' => $this->area->id,
            'opening_date' => now()->toDateString(),
            'opening_balance' => 100,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('savings.accounts.index'));

        $account = SavingsAccount::where('member_id', $this->member->id)->first();
        $this->assertNotNull($account);
        $this->assertEquals('active', $account->status);
    }

    public function test_savings_account_update_preserves_status_if_omitted(): void
    {
        $account = SavingsAccount::create([
            'account_no' => 'SAV-999',
            'member_id' => $this->member->id,
            'savings_program_id' => $this->program->id,
            'area_id' => $this->area->id,
            'opening_date' => now()->toDateString(),
            'opening_balance' => 0,
            'current_balance' => 0,
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->put(route('savings.accounts.update', $account), [
            'member_id' => $this->member->id,
            'savings_program_id' => $this->program->id,
            'area_id' => $this->area->id,
            'opening_date' => now()->toDateString(),
            'expected_deposit' => 500,
        ]);

        $response->assertSessionHasNoErrors();
        $account->refresh();
        $this->assertEquals('active', $account->status);
        $this->assertEquals(500, (float)$account->expected_deposit);
    }
}

