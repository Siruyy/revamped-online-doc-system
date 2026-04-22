<?php

namespace App\Services;

use App\Events\ClearanceUpdated;
use App\Models\Clearance;
use App\Models\User;
use App\Notifications\ClearanceCompletedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ClearanceService
{
    /**
     * @return array{status: string, remarks: string, signed_by: string, signed_at: string}
     */
    private function departmentColumns(string $department): array
    {
        return match ($department) {
            'teacher' => [
                'status' => 'teacher_status',
                'remarks' => 'teacher_remarks',
                'signed_by' => 'teacher_signed_by',
                'signed_at' => 'teacher_signed_at',
            ],
            'dean' => [
                'status' => 'dean_status',
                'remarks' => 'dean_remarks',
                'signed_by' => 'dean_signed_by',
                'signed_at' => 'dean_signed_at',
            ],
            'accounting' => [
                'status' => 'accounting_status',
                'remarks' => 'accounting_remarks',
                'signed_by' => 'accounting_signed_by',
                'signed_at' => 'accounting_signed_at',
            ],
            'sao' => [
                'status' => 'sao_status',
                'remarks' => 'sao_remarks',
                'signed_by' => 'sao_signed_by',
                'signed_at' => 'sao_signed_at',
            ],
            default => throw new \InvalidArgumentException('Invalid department role.'),
        };
    }

    public function submitFile(Clearance $clearance, UploadedFile $file): Clearance
    {
        if ($clearance->overall_status !== 'in_progress') {
            throw new \RuntimeException('Clearance supporting file can only be updated while clearance is in progress.');
        }

        if ($clearance->teacher_signed_at || $clearance->dean_signed_at || $clearance->accounting_signed_at || $clearance->sao_signed_at) {
            throw new \RuntimeException('Clearance supporting file can no longer be updated after department signing starts.');
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $path = "clearance-files/{$clearance->user_id}/".Str::uuid().".{$extension}";
        Storage::disk('local')->put($path, $file->getContent());

        $clearance->update([
            'uploaded_file_path' => $path,
        ]);

        ActivityLogger::log(
            'clearance_file_uploaded',
            "User {$clearance->user->email} uploaded a clearance supporting document.",
            $clearance->user,
            $clearance->user,
            ['clearance_id' => $clearance->id]
        );

        return $clearance->refresh();
    }

    public function signFor(Clearance $clearance, User $officer, string $department, ?string $remarks = null): Clearance
    {
        $columns = $this->departmentColumns($department);

        return DB::transaction(function () use ($clearance, $officer, $columns, $remarks, $department): Clearance {
            /** @var Clearance $locked */
            $locked = Clearance::query()->whereKey($clearance->id)->lockForUpdate()->firstOrFail();

            $beforeOverall = $locked->overall_status;

            $locked->update([
                $columns['status'] => 'cleared',
                $columns['remarks'] => $remarks,
                $columns['signed_by'] => $officer->id,
                $columns['signed_at'] => now(),
            ]);

            $locked->recomputeOverallStatus();
            $locked->save();

            ActivityLogger::log(
                'clearance_signed',
                "Officer {$officer->email} cleared {$department} for clearance #{$locked->id}.",
                $officer,
                $locked->user,
                ['clearance_id' => $locked->id, 'department' => $department]
            );

            ClearanceUpdated::dispatch(
                $locked->id,
                $locked->user_id,
                $department,
                'signed',
                $locked->overall_status
            );

            $locked->refresh();

            if ($locked->overall_status === 'completed' && $beforeOverall !== 'completed') {
                $this->ensureStubClearancePdf($locked);
                $locked->refresh();
                $locked->user->notify(new ClearanceCompletedNotification($locked));
            }

            return $locked;
        });
    }

    public function denyFor(Clearance $clearance, User $officer, string $department, string $remarks): Clearance
    {
        $columns = $this->departmentColumns($department);

        return DB::transaction(function () use ($clearance, $officer, $columns, $remarks, $department): Clearance {
            /** @var Clearance $locked */
            $locked = Clearance::query()->whereKey($clearance->id)->lockForUpdate()->firstOrFail();

            $locked->update([
                $columns['status'] => 'denied',
                $columns['remarks'] => $remarks,
                $columns['signed_by'] => $officer->id,
                $columns['signed_at'] => now(),
            ]);

            $locked->recomputeOverallStatus();
            $locked->save();

            ActivityLogger::log(
                'clearance_denied',
                "Officer {$officer->email} denied {$department} for clearance #{$locked->id}.",
                $officer,
                $locked->user,
                ['clearance_id' => $locked->id, 'department' => $department]
            );

            ClearanceUpdated::dispatch(
                $locked->id,
                $locked->user_id,
                $department,
                'denied',
                $locked->overall_status
            );

            return $locked->refresh();
        });
    }

    /**
     * Phase 09 replaces this with real PDF generation; for now we persist a minimal file so downloads work.
     */
    private function ensureStubClearancePdf(Clearance $clearance): void
    {
        if ($clearance->pdf_path && Storage::disk('local')->exists($clearance->pdf_path)) {
            return;
        }

        $relativePath = 'pdfs/clearance/'.$clearance->user_id.'/clearance-'.$clearance->id.'.pdf';
        Storage::disk('local')->put($relativePath, "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF\n");

        $clearance->update(['pdf_path' => $relativePath]);
    }
}
