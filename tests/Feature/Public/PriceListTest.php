<?php

namespace Tests\Feature\Public;

use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PriceListTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_view_only_active_document_prices(): void
    {
        $type = DocumentType::factory()->create(['name' => 'Transcript of Records', 'fee' => 165, 'fee_formula' => 'per_page']);
        DocumentType::factory()->create(['is_active' => false]);

        $this->get(route('public.pricelist'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Public/PriceList')
            ->has('documentTypes', 1)
            ->where('documentTypes.0.name', $type->name)
            ->where('documentTypes.0.fee', '165.00')
            ->where('documentTypes.0.fee_formula', 'per_page')
            ->missing('documentTypes.0.requirements')
            ->missing('documentTypes.0.created_at'));
    }

    public function test_accounting_can_change_fees_without_changing_document_configuration(): void
    {
        $accountant = User::factory()->create(['role' => 'accounting']);
        $type = DocumentType::factory()->create(['fee' => 165, 'fee_formula' => 'flat']);
        $originalName = $type->name;
        $quotedRequest = DocumentRequest::factory()->create([
            'document_type_id' => $type->id, 'quote_total' => 165, 'fee_snapshot' => 165,
        ]);

        $this->actingAs($accountant)->get(route('department.prices.index'))->assertOk();
        $this->actingAs($accountant)->from(route('department.prices.index'))
            ->patch(route('department.prices.update', $type), [
                'fee' => '180.50', 'name' => 'Tampered', 'is_active' => false, 'fee_formula' => 'per_page',
            ])->assertRedirect(route('department.prices.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('document_types', [
            'id' => $type->id, 'fee' => 180.50, 'name' => $originalName, 'is_active' => true, 'fee_formula' => 'flat',
        ]);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $accountant->id, 'action' => 'document_price_updated']);
        $this->assertSame('165.00', $quotedRequest->fresh()->quote_total);
        $this->assertSame('165.00', $quotedRequest->fresh()->fee_snapshot);
        $this->get(route('public.pricelist'))->assertInertia(fn (Assert $page) => $page->where('documentTypes.0.fee', '180.50'));
    }

    public function test_accounting_price_updates_reject_invalid_amounts(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'accounting']));
        $type = DocumentType::factory()->create(['fee' => 165]);

        foreach ([-1, 'not-a-price', 100000000, '1.123', null] as $fee) {
            $this->patch(route('department.prices.update', $type), ['fee' => $fee])->assertSessionHasErrors('fee');
        }

        $this->assertSame('165.00', $type->fresh()->fee);
    }

    public function test_guests_and_other_department_roles_cannot_manage_prices(): void
    {
        $type = DocumentType::factory()->create();
        $this->get(route('department.prices.index'))->assertRedirect(route('login'));
        $this->patch(route('department.prices.update', $type), ['fee' => 200])->assertRedirect(route('login'));

        foreach (['student', 'dean', 'principal', 'president', 'librarian', 'student_affairs', 'alumni', 'guidance'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->get(route('department.prices.index'))->assertForbidden();
            $this->patch(route('department.prices.update', $type), ['fee' => 200])->assertForbidden();
        }

        $this->actingAs(User::factory()->create(['role' => 'accounting']));
        $this->post(route('admin.document-types.store'), [])->assertForbidden();
        $this->delete(route('admin.document-types.destroy', $type))->assertForbidden();
    }

    public function test_admin_and_superadmin_catalog_edits_are_reflected_in_public_prices(): void
    {
        $type = DocumentType::factory()->create();

        foreach (['admin', 'superadmin'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->patch(route($role.'.document-types.update', $type), [
                'name' => 'Updated Record', 'description' => null, 'category' => 'Academic',
                'fee' => 220, 'default_page_count' => 1, 'processing_days' => 3, 'is_active' => true,
            ])->assertSessionHasNoErrors();
            $this->get(route('public.pricelist'))->assertInertia(fn (Assert $page) => $page
                ->where('documentTypes.0.name', 'Updated Record')
                ->where('documentTypes.0.fee', '220.00'));
        }
    }
}
