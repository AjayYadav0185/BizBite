<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        // Guests get the landing page with sign-in and the app download.
        $response->assertOk()
            ->assertSee('BizBite POS')
            ->assertSee(route('login'), false);
    }
}
