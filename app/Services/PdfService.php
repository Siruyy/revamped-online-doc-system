<?php

namespace App\Services;

use App\Models\Clearance;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PdfService
{
    public function generateClearancePdf(Clearance $clearance): string
    {
        $clearance->loadMissing([
            'user:id,fullname,email,student_id,course,year_level',
            'documentRequest:id,reference_no,purpose,status',
            'teacherSigner:id,fullname',
            'deanSigner:id,fullname',
            'accountingSigner:id,fullname',
            'saoSigner:id,fullname',
        ]);

        $relativePath = 'pdfs/clearance/'.$clearance->user_id.'/clearance-'.$clearance->id.'.pdf';

        $pdf = Pdf::loadView('pdf.clearance', [
            'clearance' => $clearance,
            'generatedAt' => now(),
        ])->setPaper('a4');

        Storage::disk('local')->put($relativePath, $pdf->output());

        $clearance->forceFill(['pdf_path' => $relativePath])->save();

        return $relativePath;
    }
}
