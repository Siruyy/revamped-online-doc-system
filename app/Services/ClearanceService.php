<?php

namespace App\Services;

use App\Models\Clearance;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ClearanceService
{
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
}
