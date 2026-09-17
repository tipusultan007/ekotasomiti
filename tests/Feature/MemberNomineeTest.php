<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Member;
use App\Models\MemberNominee;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberNomineeTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Area $area;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $this->admin = User::where('email', 'admin@ekota.com')->first();
        $this->area = Area::first();
    }

    public function test_member_can_be_created_without_nominees(): void
    {
        $response = $this->actingAs($this->admin)->post(route('members.store'), [
            'name' => 'Member Without Nominee',
            'membership_date' => now()->toDateString(),
            'gender' => 'male',
            'mobile' => '01711112222',
            'area_id' => $this->area->id,
            'status' => 'active',
            'nominees' => [],
        ]);

        $response->assertSessionHasNoErrors();
        $member = Member::where('name', 'Member Without Nominee')->first();
        $this->assertNotNull($member);
        $this->assertCount(0, $member->nominees);
    }

    public function test_member_can_be_created_with_blank_nominee_row(): void
    {
        // Simulates submitting the HTML form where an empty nominee row is present
        $response = $this->actingAs($this->admin)->post(route('members.store'), [
            'name' => 'Member Blank Nominee Row',
            'membership_date' => now()->toDateString(),
            'gender' => 'female',
            'mobile' => '01711112223',
            'area_id' => $this->area->id,
            'status' => 'active',
            'nominees' => [
                [
                    'name' => '',
                    'relationship' => '',
                    'nid' => '',
                    'mobile' => '',
                    'percentage' => '',
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $member = Member::where('name', 'Member Blank Nominee Row')->first();
        $this->assertNotNull($member);
        $this->assertCount(0, $member->nominees);
    }

    public function test_member_creation_fails_if_nominee_has_details_but_missing_name(): void
    {
        $response = $this->actingAs($this->admin)->post(route('members.store'), [
            'name' => 'Member Invalid Nominee',
            'membership_date' => now()->toDateString(),
            'gender' => 'male',
            'mobile' => '01711112224',
            'area_id' => $this->area->id,
            'status' => 'active',
            'nominees' => [
                [
                    'name' => '',
                    'relationship' => 'Brother',
                    'nid' => '',
                    'mobile' => '01900000000',
                    'percentage' => '100',
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['nominees.0.name']);
    }

    public function test_member_can_be_created_with_valid_nominee(): void
    {
        $response = $this->actingAs($this->admin)->post(route('members.store'), [
            'name' => 'Member With Nominee',
            'membership_date' => now()->toDateString(),
            'gender' => 'male',
            'mobile' => '01711112225',
            'area_id' => $this->area->id,
            'status' => 'active',
            'nominees' => [
                [
                    'name' => 'Fatima Begum',
                    'relationship' => 'Spouse',
                    'nid' => '1234567890',
                    'mobile' => '01811112222',
                    'percentage' => 100,
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $member = Member::where('name', 'Member With Nominee')->first();
        $this->assertNotNull($member);
        $this->assertCount(1, $member->nominees);
        $this->assertEquals('Fatima Begum', $member->nominees->first()->name);
    }

    public function test_nominee_can_be_added_later_from_member_profile(): void
    {
        $member = Member::create([
            'member_no' => 'M-99901',
            'name' => 'Member Add Later',
            'membership_date' => now()->toDateString(),
            'gender' => 'male',
            'mobile' => '01711112226',
            'area_id' => $this->area->id,
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $this->assertCount(0, $member->nominees);

        $response = $this->actingAs($this->admin)->post(route('members.nominees.store', $member), [
            'name' => 'Later Added Nominee',
            'relationship' => 'Son',
            'nid' => '987654321',
            'mobile' => '01911112222',
            'percentage' => 50,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $member->refresh();
        $this->assertCount(1, $member->nominees);
        $this->assertEquals('Later Added Nominee', $member->nominees->first()->name);
    }

    public function test_nominee_can_be_deleted_later(): void
    {
        $member = Member::create([
            'member_no' => 'M-99902',
            'name' => 'Member Delete Nominee',
            'membership_date' => now()->toDateString(),
            'gender' => 'female',
            'mobile' => '01711112227',
            'area_id' => $this->area->id,
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $nominee = $member->nominees()->create([
            'name' => 'To Be Deleted',
            'relationship' => 'Daughter',
        ]);

        $this->assertDatabaseHas('member_nominees', ['id' => $nominee->id]);

        $response = $this->actingAs($this->admin)->delete(route('members.nominees.destroy', $nominee));
        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseMissing('member_nominees', ['id' => $nominee->id]);
    }

    public function test_member_edit_can_add_nominee_later(): void
    {
        $member = Member::create([
            'member_no' => 'M-99903',
            'name' => 'Member Edit Nominee',
            'membership_date' => now()->toDateString(),
            'gender' => 'male',
            'mobile' => '01711112228',
            'area_id' => $this->area->id,
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->put(route('members.update', $member), [
            'name' => 'Member Edit Nominee Updated',
            'membership_date' => now()->toDateString(),
            'gender' => 'male',
            'mobile' => '01711112228',
            'area_id' => $this->area->id,
            'status' => 'active',
            'nominees' => [
                [
                    'name' => 'Edit Added Nominee',
                    'relationship' => 'Mother',
                    'nid' => '555444333',
                    'mobile' => '01511112222',
                    'percentage' => 100,
                ],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $member->refresh();
        $this->assertCount(1, $member->nominees);
        $this->assertEquals('Edit Added Nominee', $member->nominees->first()->name);
    }
}

