<?php

namespace App\Services;

use App\Models\SchoolBranding;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class SchoolBrandingService
{
    public function saveLogo(?UploadedFile $file, User $actor): void
    {
        $path = $file?->store('branding', 'local');

        if ($path === false) {
            throw new RuntimeException('Unable to store school logo.');
        }

        try {
            $oldPath = DB::transaction(function () use ($path, $actor): ?string {
                SchoolBranding::query()->firstOrCreate(['id' => 1]);
                $branding = SchoolBranding::query()->lockForUpdate()->findOrFail(1);
                $oldPath = $branding->logo_path;
                $branding->update(['logo_path' => $path]);
                ActivityLogger::log('school_branding_updated', $path ? 'School branding logo updated.' : 'School branding logo removed.', $actor);

                return $oldPath;
            });
        } catch (Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }

        if ($oldPath && str_starts_with($oldPath, 'branding/') && ! str_contains($oldPath, '..')) {
            Storage::disk('local')->delete($oldPath);
        }
    }

    public function logoDataUri(): ?string
    {
        $path = SchoolBranding::query()->find(1)?->logo_path;

        if ($path && str_starts_with($path, 'branding/') && ! str_contains($path, '..') && Storage::disk('local')->exists($path)) {
            $mime = Storage::disk('local')->mimeType($path);

            if (in_array($mime, ['image/png', 'image/jpeg'], true)) {
                return 'data:'.$mime.';base64,'.base64_encode(Storage::disk('local')->get($path));
            }
        }

        return null;
    }
}
