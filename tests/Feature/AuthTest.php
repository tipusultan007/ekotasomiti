<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Mobile Number or Email');
    }

    public function test_user_can_login_with_phone_number(): void
    {
        $response = $this->post('/login', [
            'login' => '01700000000',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_user_can_login_with_formatted_phone_number(): void
    {
        $response = $this->post('/login', [
            'login' => '+8801700000000',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_user_can_login_with_email(): void
    {
        $response = $this->post('/login', [
            'login' => 'admin@ekota.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        $response = $this->post('/login', [
            'login' => '01700000000',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['login']);
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::where('phone', '01700000000')->first();
        $user->update(['is_active' => false]);

        $response = $this->post('/login', [
            'login' => '01700000000',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['login']);
        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::where('phone', '01700000000')->first();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }
}
