<?php

namespace App\Services;

use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DocumentPricingService
{
    public function updateFee(DocumentType $documentType, float $fee, User $actor): void
    {
        DB::transaction(function () use ($documentType, $fee, $actor): void {
            $documentType->update(['fee' => $fee]);

            ActivityLogger::log(
                'document_price_updated',
                "Updated the catalog fee for {$documentType->name}.",
                $actor,
                metadata: ['document_type_id' => $documentType->id, 'fee' => $documentType->fee],
            );
        });
    }
}
