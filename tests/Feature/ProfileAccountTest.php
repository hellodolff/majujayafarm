<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_can_create_account(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($operator)
            ->post(route('profile.accounts.store'), [
                'name' => 'User Baru',
                'email' => 'baru@farmpro.test',
                'role' => 'viewer',
                'password' => 'Strong@123',
                'password_confirmation' => 'Strong@123',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'baru@farmpro.test']);
    }

    public function test_admin_can_create_account_with_strong_hashed_password(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('profile.accounts.store'), [
                'name' => 'User Baru',
                'email' => 'baru@farmpro.test',
                'role' => 'viewer',
                'password' => 'Strong@123',
                'password_confirmation' => 'Strong@123',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $account = User::where('email', 'baru@farmpro.test')->firstOrFail();

        $this->assertSame('viewer', $account->role);
        $this->assertTrue(Hash::check('Strong@123', $account->password));
        $this->assertNotSame('Strong@123', $account->password);
    }

    public function test_password_must_meet_strength_rules(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('profile.accounts.store'), [
                'name' => 'User Lemah',
                'email' => 'lemah@farmpro.test',
                'role' => 'operator',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'lemah@farmpro.test']);
    }
}
