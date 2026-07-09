<?php

namespace App\Services;

use App\Models\Clearance;
use App\Support\ClearanceSignatories;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PdfService
{
    public function generateClearancePdf(Clearance $clearance): string
    {
        $clearance->loadMissing([
            'user:id,fullname,email,student_id,course,year_level',
            'documentRequest:id,reference_no,purpose,status,requester_name,requester_email,requester_student_id,requester_course,requester_year_level',
            ...collect(ClearanceSignatories::signerRelations())
                ->map(fn (string $relation): string => "{$relation}:id,fullname")
                ->all(),
        ]);

        $relativePath = $clearance->user_id !== null
            ? 'pdfs/clearance/'.$clearance->user_id.'/clearance-'.$clearance->id.'.pdf'
            : 'pdfs/clearance/public/'.$clearance->document_request_id.'/clearance-'.$clearance->id.'.pdf';

        $pdf = Pdf::loadView('pdf.clearance', [
            'clearance' => $clearance,
            'generatedAt' => now(),
            'signatories' => ClearanceSignatories::definitions(),
        ])->setPaper('a4');

        Storage::disk('local')->put($relativePath, $pdf->output());

        $clearance->forceFill(['pdf_path' => $relativePath])->save();

        return $relativePath;
    }
}
