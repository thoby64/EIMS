<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_guest_is_directed_to_the_eims_sign_in_screen(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
        $this->get('/login')->assertOk()
            ->assertSee('Enterprise Infrastructure Management System')
            ->assertDontSee('Argon Dashboard');
    }
}
