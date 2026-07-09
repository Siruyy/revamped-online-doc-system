<?php

namespace Tests\Unit\Services;

use App\Events\ClearanceCompleted;
use App\Events\ClearanceUpdated;
use App\Models\Clearance;
use App\Models\DocumentRequest;
use App\Models\User;
use App\Notifications\ClearanceCompletedNotification;
use App\Services\ClearanceService;
use App\Support\ClearanceSignatories;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ClearanceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_submits_supporting_file_before_signing_starts(): void
    {
        Storage::fake('local');

        $student = User::factory()->student()->create();
        $request = DocumentRequest::factory()->for($student)->approved()->create();
        $clearance = Clearance::factory()->for($student)->for($request)->inProgress()->create();

        $updated = $this->service()->submitFile(
            $clearance,
            UploadedFile::fake()->create('support.PDF', 20, 'application/pdf')
        );

        $this->assertStringStartsWith("clearance-files/{$student->id}/", $updated->uploaded_file_path);
        $this->assertStringEndsWith('.pdf', $updated->uploaded_file_path);
        Storage::disk('local')->assertExists($updated->uploaded_file_path);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'clearance_file_uploaded',
            'user_id' => $student->id,
            'affected_user_id' => $student->id,
        ]);
    }

    public function test_it_prevents_file_submission_after_signing_starts(): void
    {
        Storage::fake('local');

        $student = User::factory()->student()->create();
        $clearance = Clearance::factory()->for($student)->create([
            'dean_status' => 'cleared',
            'dean_signed_by' => User::factory()->dean()->create()->id,
            'dean_signed_at' => now(),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('after department signing starts');

        $this->service()->submitFile($clearance, UploadedFile::fake()->create('support.pdf'));
    }

    public function test_it_signs_department_and_recomputes_in_progress_status(): void
    {
        Event::fake([ClearanceUpdated::class]);
        Notification::fake();

        $dean = User::factory()->dean()->create();
        $student = User::factory()->student()->create();
        $clearance = Clearance::factory()->for($student)->create([
            'dean_status' => 'pending',
            'uploaded_file_path' => "clearance-files/{$student->id}/support.pdf",
        ]);

        $updated = $this->service()->signFor($clearance, $dean, 'dean', 'Verified');

        $this->assertSame('cleared', $updated->dean_status);
        $this->assertSame('Verified', $updated->dean_remarks);
        $this->assertSame($dean->id, $updated->dean_signed_by);
        $this->assertNotNull($updated->dean_signed_at);
        $this->assertSame('in_progress', $updated->overall_status);
        Event::assertDispatched(ClearanceUpdated::class, fn (ClearanceUpdated $event) => $event->clearanceId === $updated->id
            && $event->studentId === $student->id
            && $event->department === 'dean'
            && $event->action === 'signed'
            && $event->overallStatus === 'in_progress'
        );
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'clearance_signed',
            'user_id' => $dean->id,
            'affected_user_id' => $student->id,
        ]);
    }

    public function test_it_signs_each_department_column_independently(): void
    {
        Event::fake([ClearanceUpdated::class]);
        Notification::fake();

        foreach ($this->signatoryRoles() as $role) {
            $officer = User::factory()->signatory($role)->create();
            $student = User::factory()->student()->create();
            $clearance = Clearance::factory()->for($student)->create([
                'uploaded_file_path' => "clearance-files/{$student->id}/support.pdf",
            ]);

            $updated = $this->service()->signFor($clearance, $officer, $role, "{$role} clear");

            $this->assertSame('cleared', $updated->getAttribute("{$role}_status"));
            $this->assertSame("{$role} clear", $updated->getAttribute("{$role}_remarks"));
            $this->assertSame($officer->id, $updated->getAttribute("{$role}_signed_by"));
            $this->assertNotNull($updated->getAttribute("{$role}_signed_at"));

            foreach (array_diff($this->signatoryRoles(), [$role]) as $otherRole) {
                $this->assertSame('pending', $updated->getAttribute("{$otherRole}_status"));
                $this->assertNull($updated->getAttribute("{$otherRole}_signed_by"));
            }
        }
    }

    public function test_it_prevents_signing_when_clearance_is_not_in_progress(): void
    {
        Event::fake([ClearanceUpdated::class]);
        Notification::fake();

        $student = User::factory()->student()->create();
        $clearance = Clearance::factory()->for($student)->create([
            'dean_status' => 'pending',
            'overall_status' => 'denied',
            'uploaded_file_path' => "clearance-files/{$student->id}/support.pdf",
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Clearance can only be signed while it is in progress.');

        $this->service()->signFor($clearance, User::factory()->dean()->create(), 'dean');
    }

    public function test_it_prevents_signing_when_department_status_is_not_pending(): void
    {
        Event::fake([ClearanceUpdated::class]);
        Notification::fake();

        $student = User::factory()->student()->create();
        $clearance = Clearance::factory()->for($student)->create([
            'dean_status' => 'cleared',
            'overall_status' => 'in_progress',
            'uploaded_file_path' => "clearance-files/{$student->id}/support.pdf",
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('This department clearance is no longer pending.');

        $this->service()->signFor($clearance, User::factory()->dean()->create(), 'dean');
    }

    public function test_it_prevents_signing_without_uploaded_supporting_file(): void
    {
        Event::fake([ClearanceUpdated::class]);
        Notification::fake();

        $student = User::factory()->student()->create();
        $clearance = Clearance::factory()->for($student)->create([
            'dean_status' => 'pending',
            'overall_status' => 'in_progress',
            'uploaded_file_path' => null,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Student must upload the clearance supporting file before department signing.');

        $this->service()->signFor($clearance, User::factory()->dean()->create(), 'dean');
    }

    public function test_it_prevents_signing_for_a_different_department_than_the_officer_role(): void
    {
        Event::fake([ClearanceUpdated::class]);
        Notification::fake();

        $student = User::factory()->student()->create();
        $clearance = Clearance::factory()->for($student)->create([
            'dean_status' => 'pending',
            'president_status' => 'pending',
            'overall_status' => 'in_progress',
            'uploaded_file_path' => "clearance-files/{$student->id}/support.pdf",
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Officer role does not match the clearance signatory office.');

        $this->service()->signFor($clearance, User::factory()->signatory('president')->create(), 'dean');
    }

    public function test_it_denies_department_and_recomputes_denied_status(): void
    {
        Event::fake([ClearanceUpdated::class]);
        Notification::fake();

        $dean = User::factory()->dean()->create();
        $student = User::factory()->student()->create();
        $clearance = Clearance::factory()->for($student)->create([
            'dean_status' => 'pending',
        ]);

        $updated = $this->service()->denyFor($clearance, $dean, 'dean', 'Missing library clearance paperwork');

        $this->assertSame('denied', $updated->dean_status);
        $this->assertSame('Missing library clearance paperwork', $updated->dean_remarks);
        $this->assertSame($dean->id, $updated->dean_signed_by);
        $this->assertSame('denied', $updated->overall_status);
        $this->assertNull($updated->completed_at);
        Event::assertDispatched(ClearanceUpdated::class, fn (ClearanceUpdated $event) => $event->clearanceId === $updated->id
            && $event->studentId === $student->id
            && $event->department === 'dean'
            && $event->action === 'denied'
            && $event->overallStatus === 'denied'
        );
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'clearance_denied',
            'user_id' => $dean->id,
            'affected_user_id' => $student->id,
        ]);
    }

    public function test_it_denies_each_department_column_independently(): void
    {
        Event::fake([ClearanceUpdated::class]);
        Notification::fake();

        foreach ($this->signatoryRoles() as $role) {
            $officer = User::factory()->signatory($role)->create();
            $student = User::factory()->student()->create();
            $clearance = Clearance::factory()->for($student)->create();

            $updated = $this->service()->denyFor($clearance, $officer, $role, "{$role} requirement missing");

            $this->assertSame('denied', $updated->getAttribute("{$role}_status"));
            $this->assertSame("{$role} requirement missing", $updated->getAttribute("{$role}_remarks"));
            $this->assertSame($officer->id, $updated->getAttribute("{$role}_signed_by"));
            $this->assertNotNull($updated->getAttribute("{$role}_signed_at"));
            $this->assertSame('denied', $updated->overall_status);

            foreach (array_diff($this->signatoryRoles(), [$role]) as $otherRole) {
                $this->assertSame('pending', $updated->getAttribute("{$otherRole}_status"));
                $this->assertNull($updated->getAttribute("{$otherRole}_signed_by"));
            }
        }
    }

    public function test_it_prevents_denial_when_clearance_is_not_in_progress(): void
    {
        Event::fake([ClearanceUpdated::class]);
        Notification::fake();

        $student = User::factory()->student()->create();
        $clearance = Clearance::factory()->for($student)->create([
            'dean_status' => 'pending',
            'overall_status' => 'completed',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Clearance can only be denied while it is in progress.');

        $this->service()->denyFor($clearance, User::factory()->dean()->create(), 'dean', 'Missing paperwork');
    }

    public function test_it_prevents_denial_when_department_status_is_not_pending(): void
    {
        Event::fake([ClearanceUpdated::class]);
        Notification::fake();

        $student = User::factory()->student()->create();
        $clearance = Clearance::factory()->for($student)->create([
            'dean_status' => 'cleared',
            'overall_status' => 'in_progress',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('This department clearance is no longer pending.');

        $this->service()->denyFor($clearance, User::factory()->dean()->create(), 'dean', 'Missing paperwork');
    }

    public function test_it_prevents_denial_for_a_different_department_than_the_officer_role(): void
    {
        Event::fake([ClearanceUpdated::class]);
        Notification::fake();

        $student = User::factory()->student()->create();
        $clearance = Clearance::factory()->for($student)->create([
            'dean_status' => 'pending',
            'president_status' => 'pending',
            'overall_status' => 'in_progress',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Officer role does not match the clearance signatory office.');

        $this->service()->denyFor($clearance, User::factory()->signatory('president')->create(), 'dean', 'Missing paperwork');
    }

    public function test_it_completes_clearance_once_all_departments_sign(): void
    {
        Event::fake([ClearanceUpdated::class, ClearanceCompleted::class]);
        Notification::fake();
        Storage::fake('local');

        $student = User::factory()->student()->create();
        $request = DocumentRequest::factory()->for($student)->approved()->create();
        $clearance = Clearance::factory()->for($student)->for($request)->create([
            'dean_status' => 'cleared',
            'president_status' => 'cleared',
            'librarian_status' => 'cleared',
            'student_affairs_status' => 'cleared',
            'alumni_status' => 'cleared',
            'guidance_status' => 'pending',
            'overall_status' => 'in_progress',
            'uploaded_file_path' => "clearance-files/{$student->id}/support.pdf",
        ]);

        $updated = $this->service()->signFor($clearance, User::factory()->signatory('guidance')->create(), 'guidance');

        $this->assertSame('completed', $updated->overall_status);
        $this->assertNotNull($updated->completed_at);
        $this->assertStringStartsWith('pdfs/clearance/', $updated->pdf_path);
        Storage::disk('local')->assertExists($updated->pdf_path);
        Event::assertDispatched(ClearanceCompleted::class);
        Notification::assertSentTo($student, ClearanceCompletedNotification::class);
    }

    private function service(): ClearanceService
    {
        return $this->app->make(ClearanceService::class);
    }

    /**
     * @return array<int, string>
     */
    private function signatoryRoles(): array
    {
        return ClearanceSignatories::roles();
    }
}
