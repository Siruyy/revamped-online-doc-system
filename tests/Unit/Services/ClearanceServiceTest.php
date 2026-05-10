<?php

namespace Tests\Unit\Services;

use App\Events\ClearanceCompleted;
use App\Events\ClearanceUpdated;
use App\Models\Clearance;
use App\Models\DocumentRequest;
use App\Models\User;
use App\Notifications\ClearanceCompletedNotification;
use App\Services\ClearanceService;
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
            'teacher_status' => 'cleared',
            'teacher_signed_by' => User::factory()->teacher()->create()->id,
            'teacher_signed_at' => now(),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('after department signing starts');

        $this->service()->submitFile($clearance, UploadedFile::fake()->create('support.pdf'));
    }

    public function test_it_signs_department_and_recomputes_in_progress_status(): void
    {
        Event::fake([ClearanceUpdated::class]);
        Notification::fake();

        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->student()->create();
        $clearance = Clearance::factory()->for($student)->create([
            'teacher_status' => 'pending',
            'dean_status' => 'pending',
            'accounting_status' => 'pending',
            'sao_status' => 'pending',
        ]);

        $updated = $this->service()->signFor($clearance, $teacher, 'teacher', 'Verified');

        $this->assertSame('cleared', $updated->teacher_status);
        $this->assertSame('Verified', $updated->teacher_remarks);
        $this->assertSame($teacher->id, $updated->teacher_signed_by);
        $this->assertNotNull($updated->teacher_signed_at);
        $this->assertSame('in_progress', $updated->overall_status);
        Event::assertDispatched(ClearanceUpdated::class, fn (ClearanceUpdated $event) => $event->clearanceId === $updated->id
            && $event->studentId === $student->id
            && $event->department === 'teacher'
            && $event->action === 'signed'
            && $event->overallStatus === 'in_progress'
        );
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'clearance_signed',
            'user_id' => $teacher->id,
            'affected_user_id' => $student->id,
        ]);
    }

    public function test_it_signs_each_department_column_independently(): void
    {
        Event::fake([ClearanceUpdated::class]);
        Notification::fake();

        foreach (['teacher', 'dean', 'accounting', 'sao'] as $role) {
            $officer = User::factory()->{$role}()->create();
            $student = User::factory()->student()->create();
            $clearance = Clearance::factory()->for($student)->create([
                'teacher_status' => 'pending',
                'dean_status' => 'pending',
                'accounting_status' => 'pending',
                'sao_status' => 'pending',
            ]);

            $updated = $this->service()->signFor($clearance, $officer, $role, "{$role} clear");

            $this->assertSame('cleared', $updated->getAttribute("{$role}_status"));
            $this->assertSame("{$role} clear", $updated->getAttribute("{$role}_remarks"));
            $this->assertSame($officer->id, $updated->getAttribute("{$role}_signed_by"));
            $this->assertNotNull($updated->getAttribute("{$role}_signed_at"));

            foreach (array_diff(['teacher', 'dean', 'accounting', 'sao'], [$role]) as $otherRole) {
                $this->assertSame('pending', $updated->getAttribute("{$otherRole}_status"));
                $this->assertNull($updated->getAttribute("{$otherRole}_signed_by"));
            }
        }
    }

    public function test_it_denies_department_and_recomputes_denied_status(): void
    {
        Event::fake([ClearanceUpdated::class]);
        Notification::fake();

        $dean = User::factory()->dean()->create();
        $student = User::factory()->student()->create();
        $clearance = Clearance::factory()->for($student)->create([
            'teacher_status' => 'cleared',
            'dean_status' => 'pending',
            'accounting_status' => 'pending',
            'sao_status' => 'pending',
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

        foreach (['teacher', 'dean', 'accounting', 'sao'] as $role) {
            $officer = User::factory()->{$role}()->create();
            $student = User::factory()->student()->create();
            $clearance = Clearance::factory()->for($student)->create([
                'teacher_status' => 'pending',
                'dean_status' => 'pending',
                'accounting_status' => 'pending',
                'sao_status' => 'pending',
            ]);

            $updated = $this->service()->denyFor($clearance, $officer, $role, "{$role} requirement missing");

            $this->assertSame('denied', $updated->getAttribute("{$role}_status"));
            $this->assertSame("{$role} requirement missing", $updated->getAttribute("{$role}_remarks"));
            $this->assertSame($officer->id, $updated->getAttribute("{$role}_signed_by"));
            $this->assertNotNull($updated->getAttribute("{$role}_signed_at"));
            $this->assertSame('denied', $updated->overall_status);

            foreach (array_diff(['teacher', 'dean', 'accounting', 'sao'], [$role]) as $otherRole) {
                $this->assertSame('pending', $updated->getAttribute("{$otherRole}_status"));
                $this->assertNull($updated->getAttribute("{$otherRole}_signed_by"));
            }
        }
    }

    public function test_it_completes_clearance_once_all_departments_sign(): void
    {
        Event::fake([ClearanceUpdated::class, ClearanceCompleted::class]);
        Notification::fake();
        Storage::fake('local');

        $student = User::factory()->student()->create();
        $request = DocumentRequest::factory()->for($student)->approved()->create();
        $clearance = Clearance::factory()->for($student)->for($request)->create([
            'teacher_status' => 'cleared',
            'dean_status' => 'cleared',
            'accounting_status' => 'cleared',
            'sao_status' => 'pending',
            'overall_status' => 'in_progress',
        ]);

        $updated = $this->service()->signFor($clearance, User::factory()->sao()->create(), 'sao');

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
}
