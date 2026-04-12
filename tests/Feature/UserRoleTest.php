<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_factory_role_is_nasabah(): void
    {
        $user = User::factory()->create();

        $this->assertSame(UserRole::Nasabah, $user->role);
        $this->assertTrue($user->isNasabah());
    }

    public function test_role_helpers_return_expected_values(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->owner()->create();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isOwner());
        $this->assertTrue($admin->hasRole(UserRole::Admin));

        $this->assertTrue($owner->isOwner());
        $this->assertFalse($owner->isAdmin());
    }

    public function test_new_registration_defaults_to_nasabah(): void
    {
        $this->skipUnlessFortifyHas('registration');

        $this->post('/register', [
            'name' => 'Siti Nasabah',
            'email' => 'siti@banksampah.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect();

        $user = User::where('email', 'siti@banksampah.test')->firstOrFail();
        $this->assertSame(UserRole::Nasabah, $user->role);
    }
}
