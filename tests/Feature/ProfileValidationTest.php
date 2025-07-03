<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_update_requires_name(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => '',
                'email' => 'test@example.com',
            ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_profile_update_requires_email(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => '',
            ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_profile_update_requires_valid_email_format(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'invalid-email-format',
            ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_profile_update_email_must_be_unique(): void
    {
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'existing@example.com',
            ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_profile_update_name_length_validation(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => str_repeat('a', 256), // 256 characters, exceeds max:255
                'email' => 'test@example.com',
            ]);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_profile_update_email_must_be_lowercase(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'TEST@EXAMPLE.COM',
            ]);

        // Should pass but email should be converted to lowercase
        $response->assertSessionHasNoErrors();
        
        $user->refresh();
        $this->assertEquals('test@example.com', $user->email);
    }

    public function test_profile_page_requires_authentication(): void
    {
        $response = $this->get('/profile');

        $response->assertRedirect('/login');
    }

    public function test_profile_update_requires_authentication(): void
    {
        $response = $this->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $response->assertRedirect('/login');
    }
}