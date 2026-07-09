<?php

namespace Tests\Unit\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_scopes_return_expected_subsets(): void
    {
        $students = User::factory()->student()->count(2)->create();
        $admin = User::factory()->admin()->create();
        $pendingDean = User::factory()->dean()->pending()->create();

        $this->assertCount(
            2,
            User::query()->students()->whereIn('id', $students->pluck('id'))->get()
        );
        $this->assertCount(
            2,
            User::query()->staff()->whereIn('id', [$admin->id, $pendingDean->id])->get()
        );
        $this->assertCount(
            1,
            User::query()->pending()->whereKey($pendingDean->id)->get()
        );
        $this->assertCount(
            3,
            User::query()->active()->whereIn('id', [...$students->pluck('id')->all(), $admin->id])->get()
        );
    }

    public function test_user_role_helper_methods(): void
    {
        $student = User::factory()->student()->create();
        $admin = User::factory()->admin()->create();
        $department = User::factory()->dean()->create();
        $superadmin = User::factory()->superadmin()->create();

        $this->assertTrue($student->isStudent());
        $this->assertFalse($student->isAdmin());

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isDepartment());

        $this->assertTrue($department->isDepartment());
        $this->assertFalse($department->isSuperAdmin());

        $this->assertTrue($superadmin->isSuperAdmin());
    }
}
