<?php

namespace Tests\Feature\Department;

use App\Events\ClearanceCompleted;
use App\Events\ClearanceUpdated;
use App\Models\Clearance;
use App\Models\DocumentRequest;
use App\Models\User;
use App\Services\ClearanceService;
use App\Services\PdfService;
use Database\Seeders\ClearanceSignatorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ClearanceSignatoryWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function signatoryRoles(): array
    {
        return [
            'dean' => 'Dean',
            'president' => 'Office of the President',
            'librarian' => 'Librarian',
            'student_affairs' => 'Dean of Student Affairs',
            'alumni' => 'SVC Alumni Officer',
            'guidance' => 'Guidance Counselor',
        ];
    }

    public function test_all_required_clearance_signatories_must_clear_before_completion(): void
    {
        Event::fake([ClearanceCompleted::class, ClearanceUpdated::class]);
        Notification::fake();
        $this->app->instance(PdfService::class, new class extends PdfService
        {
            public function generateClearancePdf(Clearance $clearance): string
            {
                $clearance->forceFill(['pdf_path' => 'pdfs/test-clearance.pdf'])->save();

                return 'pdfs/test-clearance.pdf';
            }
        });

        $student = User::factory()->student()->create();
        $request = DocumentRequest::factory()->for($student)->approved()->create();
        $clearance = Clearance::factory()->for($student)->for($request)->create([
            'uploaded_file_path' => "clearance-files/{$student->id}/support.pdf",
        ]);

        foreach (array_keys($this->signatoryRoles()) as $role) {
            $officer = User::factory()->signatory($role)->create();

            $updated = app(ClearanceService::class)->signFor($clearance->refresh(), $officer, $role, "{$role} cleared");

            $this->assertSame('cleared', $updated->getAttribute("{$role}_status"));
            $this->assertSame($officer->id, $updated->getAttribute("{$role}_signed_by"));

            if ($role !== 'guidance') {
                $this->assertSame('in_progress', $updated->overall_status);
            }
        }

        $clearance->refresh();

        $this->assertSame('completed', $clearance->overall_status);
        $this->assertNotNull($clearance->completed_at);
        Event::assertDispatched(ClearanceCompleted::class);
    }

    public function test_signatory_portal_scope_uses_the_current_office(): void
    {
        $student = User::factory()->student()->create();
        $request = DocumentRequest::factory()->for($student)->approved()->create();
        $clearance = Clearance::factory()->for($student)->for($request)->create([
            'dean_status' => 'cleared',
            'president_status' => 'pending',
            'uploaded_file_path' => "clearance-files/{$student->id}/support.pdf",
        ]);

        $president = User::factory()->signatory('president')->create();

        $this->actingAs($president)
            ->get(route('department.clearances.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('departmentStatusColumn', 'president_status')
                ->has('signatories')
            );

        $this->actingAs($president)
            ->get(route('department.clearances.show', $clearance))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('department', 'president')
                ->where('currentSignatory.label', 'Office of the President')
            );
    }

    public function test_default_seeder_creates_complete_clearance_signatory_accounts(): void
    {
        $this->seed(ClearanceSignatorySeeder::class);

        foreach ($this->signatoryRoles() as $role => $label) {
            $this->assertDatabaseHas('users', [
                'email' => "{$role}@svci.test",
                'role' => $role,
                'fullname' => $label,
                'status' => 'active',
            ]);
        }
    }
}
