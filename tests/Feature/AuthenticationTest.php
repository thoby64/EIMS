<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_user_can_sign_in_with_email_and_sign_out(): void
    {
        $this->seed();

        $response = $this->post('/login', [
            'identity' => 'admin@eims.local',
            'password' => 'Eims@2026!ChangeMe',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs(User::where('email', 'admin@eims.local')->firstOrFail());

        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $this->seed();

        $this->from('/login')->post('/login', [
            'identity' => 'admin@eims.local',
            'password' => 'incorrect-password',
        ])->assertRedirect('/login')->assertSessionHasErrors('identity');

        $this->assertGuest();
    }
}
