<?php

namespace App\Services;

use App\Models\Clearance;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PdfService
{
    public function generateClearancePdf(Clearance $clearance): string
    {
        if (! $clearance->user_id) {
            throw new \RuntimeException('Clearance missing user.');
        }

        $clearance->loadMissing([
            'user:id,fullname,email,student_id,course,year_level',
            'documentRequest:id,reference_no,purpose,status',
            'teacherSigner:id,fullname,signature_path',
            'deanSigner:id,fullname,signature_path',
            'accountingSigner:id,fullname,signature_path',
            'saoSigner:id,fullname,signature_path',
        ]);

        $relativePath = 'pdfs/clearance/'.$clearance->user_id.'/clearance-'.$clearance->id.'.pdf';

        $pdf = Pdf::loadView('pdf.clearance', [
            'clearance' => $clearance,
            'generatedAt' => now(),
            'signatureImages' => $this->signatureImages($clearance),
        ])->setPaper('a4');

        Storage::disk('local')->put($relativePath, $pdf->output());

        $clearance->forceFill(['pdf_path' => $relativePath])->save();

        return $relativePath;
    }

    /**
     * @return array<string, string>
     */
    private function signatureImages(Clearance $clearance): array
    {
        $images = [];

        foreach (['teacher', 'dean', 'accounting', 'sao'] as $role) {
            $signer = $clearance->{$role.'Signer'};

            if (! $signer instanceof User) {
                continue;
            }

            $path = $signer->signature_path;

            if (! $path || ! str_starts_with($path, "signatures/{$signer->id}/") || str_contains($path, '..') || ! Storage::disk('local')->exists($path)) {
                continue;
            }

            $mime = Storage::disk('local')->mimeType($path) ?: 'image/png';
            $images[$role] = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('local')->get($path));
        }

        return $images;
    }
}
