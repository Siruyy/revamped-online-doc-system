<?php

namespace App\Services;

use App\Events\RequestSubmitted;
use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RequestService
{
    /**
     * @param  array<int, int|string>  $documentIds
     * @return array{requests: \Illuminate\Support\Collection<int, DocumentRequest>, payment: Payment}
     */
    public function createRequestBatch(User $user, array $documentIds, ?string $purpose = null): array
    {
        return DB::transaction(function () use ($user, $documentIds, $purpose): array {
            // Lock the user row so duplicate concurrent submissions cannot bypass active-request checks.
            User::query()->whereKey($user->id)->lockForUpdate()->first();

            if ($user->documentRequests()->whereIn('status', ['pending', 'approved'])->exists()) {
                throw new \RuntimeException('You still have an active request in progress.');
            }

            $documentTypes = DocumentType::query()
                ->whereIn('id', $documentIds)
                ->where('is_active', true)
                ->get();

            if ($documentTypes->count() !== count($documentIds)) {
                throw new \InvalidArgumentException('One or more selected document types are invalid.');
            }

            $createdRequests = collect();

            foreach ($documentTypes as $documentType) {
                $createdRequests->push(DocumentRequest::query()->create([
                    'user_id' => $user->id,
                    'document_type_id' => $documentType->id,
                    'status' => 'pending',
                    'processing_stage' => 'not_started',
                    'purpose' => $purpose,
                ]));
            }

            /** @var DocumentRequest $firstRequest */
            $firstRequest = $createdRequests->first();

            $payment = Payment::query()->create([
                'user_id' => $user->id,
                'document_request_id' => $firstRequest->id,
                'total_amount' => $documentTypes->sum('fee'),
                'status' => 'pending',
                'submitted_at' => now(),
            ]);

            ActivityLogger::log(
                'request_submitted',
                "User {$user->email} submitted ".count($documentIds).' document request(s).',
                $user,
                $user,
                ['document_request_ids' => $createdRequests->pluck('id')->all(), 'payment_id' => $payment->id]
            );

            RequestSubmitted::dispatch($createdRequests->pluck('id')->all(), $payment->id, $user->id);

            return [
                'requests' => $createdRequests,
                'payment' => $payment,
            ];
        });
    }
}
