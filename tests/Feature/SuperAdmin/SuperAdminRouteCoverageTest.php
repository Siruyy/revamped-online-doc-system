<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminRouteCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_access_admin_resource_alias_pages(): void
    {
        $superAdmin = User::factory()->superadmin()->create();

        foreach ([
            'superadmin.requests.index',
            'superadmin.document-types.index',
            'superadmin.announcements.index',
            'superadmin.faqs.index',
        ] as $routeName) {
            $this->actingAs($superAdmin)
                ->get(route($routeName))
                ->assertOk();
        }
    }

    public function test_superadmin_can_access_report_export_aliases(): void
    {
        $superAdmin = User::factory()->superadmin()->create();

        foreach ([
            'superadmin.reports.exports.requests',
            'superadmin.reports.exports.payments',
        ] as $routeName) {
            $this->actingAs($superAdmin)
                ->get(route($routeName))
                ->assertOk();
        }
    }

    public function test_admin_cannot_access_superadmin_aliases(): void
    {
        $admin = User::factory()->admin()->create();

        foreach ([
            'superadmin.requests.index',
            'superadmin.document-types.index',
            'superadmin.reports.exports.requests',
        ] as $routeName) {
            $this->actingAs($admin)
                ->get(route($routeName))
                ->assertForbidden();
        }
    }
}
