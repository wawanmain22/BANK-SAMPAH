<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_nasabah_is_forbidden_from_admin_dashboard(): void
    {
        $this->actingAs(User::factory()->nasabah()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_owner_can_access_admin_dashboard(): void
    {
        $this->actingAs(User::factory()->owner()->create())
            ->get(route('admin.dashboard'))
            ->assertOk();
    }
}
