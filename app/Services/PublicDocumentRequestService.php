<?php

namespace App\Services;

use App\Models\DocumentRequest;
use App\Models\DocumentRequestItem;
use App\Models\DocumentType;
use App\Models\Payment;
use App\Models\RequestRequirement;
use App\Models\User;
use App\Notifications\WorkflowStatusNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicDocumentRequestService
{
    public function __construct(private RequestService $requests) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{request: DocumentRequest, payment: Payment}
     */
    public function create(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $items = (array) $data['items'];
            $documentTypeIds = collect($items)
                ->pluck('document_type_id')
                ->map(fn (mixed $id): int => (int) $id)
                ->unique()
                ->values();

            $documentTypes = DocumentType::query()
                ->whereIn('id', $documentTypeIds)
                ->where('is_active', true)
                ->get()
                ->keyBy('id');

            if ($documentTypes->count() !== $documentTypeIds->count()) {
                throw new \RuntimeException('One or more selected document types are inactive or unavailable.');
            }

            $resolvedItems = [];

            foreach ($items as $item) {
                /** @var DocumentType $type */
                $type = $documentTypes->get((int) $item['document_type_id']);
                $copies = max(1, (int) $item['copies']);
                $pageCount = max(1, (int) ($type->default_page_count ?: 1));
                $feePerPage = (float) $type->fee;
                $lineTotal = $this->requests->computeLineTotal($type, $pageCount, $copies);

                $resolvedItems[] = [
                    'type' => $type,
                    'copies' => $copies,
                    'page_count' => $pageCount,
                    'fee_per_page' => $feePerPage,
                    'line_total' => $lineTotal,
                ];
            }

            $totalFee = array_sum(array_column($resolvedItems, 'line_total'));
            /** @var DocumentType $primaryType */
            $primaryType = $resolvedItems[0]['type'];

            $documentRequest = DocumentRequest::query()->create([
                'reference_no' => $this->generateReferenceNumber(),
                'user_id' => null,
                'requester_name' => $data['requester_name'],
                'requester_email' => $data['requester_email'] ?? null,
                'requester_contact_number' => $data['requester_contact_number'],
                'requester_student_id' => $data['requester_student_id'] ?? null,
                'requester_course' => $data['requester_course'] ?? null,
                'requester_year_level' => $data['requester_year_level'] ?? null,
                'document_type_id' => $primaryType->id,
                'quantity' => array_sum(array_column($resolvedItems, 'copies')),
                'page_count' => null,
                'fee_snapshot' => $totalFee,
                'status' => 'pending',
                'processing_stage' => 'not_started',
                'intake_mode' => 'public',
                'purpose' => $data['purpose'],
                'requires_hd_return' => collect($resolvedItems)->contains(
                    fn (array $item): bool => $item['type']->hasFlag('requires_hd_return')
                ),
                'transfer_exception_requested' => false,
            ]);

            foreach ($resolvedItems as $item) {
                DocumentRequestItem::query()->create([
                    'document_request_id' => $documentRequest->id,
                    'document_type_id' => $item['type']->id,
                    'copies' => $item['copies'],
                    'page_count_snapshot' => $item['page_count'],
                    'fee_per_page_snapshot' => $item['fee_per_page'],
                    'line_total' => $item['line_total'],
                ]);
            }

            $requiredKeys = collect($resolvedItems)
                ->flatMap(fn (array $item): array => (array) $item['type']->requirements)
                ->unique()
                ->values()
                ->all();

            $this->seedSubmittedRequirements($documentRequest, $requiredKeys, (array) ($data['requirements'] ?? []));

            /** @var UploadedFile $receipt */
            $receipt = $data['receipt'];
            $receiptPath = $this->storeUploadedFile($receipt, "payment-receipts/public/{$documentRequest->id}");

            $payment = Payment::query()->create([
                'user_id' => null,
                'document_request_id' => $documentRequest->id,
                'total_amount' => $totalFee,
                'receipt_path' => $receiptPath,
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['payment_reference_number'] ?? null,
                'status' => 'pending_approval',
                'submitted_at' => now(),
            ]);

            ActivityLogger::log(
                'public_request_submitted',
                "Public request {$documentRequest->reference_no} was submitted by {$documentRequest->requester_name}.",
                null,
                null,
                ['document_request_id' => $documentRequest->id, 'payment_id' => $payment->id]
            );

            Notification::send(
                User::query()->whereIn('role', ['admin', 'superadmin'])->where('status', 'active')->get(),
                new WorkflowStatusNotification([
                    'type' => 'request_submitted',
                    'title' => 'New public document request',
                    'message' => "{$documentRequest->requester_name} submitted a public document request.",
                    'document_request_id' => $documentRequest->id,
                    'payment_id' => $payment->id,
                ]),
            );

            return ['request' => $documentRequest->refresh(), 'payment' => $payment->refresh()];
        });
    }

    /**
     * @param  array<int, string>  $requirementKeys
     * @param  array<string, UploadedFile>  $files
     */
    private function seedSubmittedRequirements(DocumentRequest $request, array $requirementKeys, array $files): void
    {
        $catalog = config('policy.requirements', []);

        foreach ($requirementKeys as $key) {
            $file = $files[$key] ?? null;
            $path = $file instanceof UploadedFile
                ? $this->storeUploadedFile($file, "request-requirements/public/{$request->id}")
                : null;

            RequestRequirement::query()->updateOrCreate(
                ['document_request_id' => $request->id, 'requirement_key' => $key],
                [
                    'label' => $catalog[$key]['label'] ?? $key,
                    'status' => $path ? 'submitted' : 'missing',
                    'file_path' => $path,
                ]
            );
        }
    }

    private function storeUploadedFile(UploadedFile $file, string $directory): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $directory.'/'.Str::uuid().'.'.$extension;

        Storage::disk('local')->put($path, $file->getContent());

        return $path;
    }

    private function generateReferenceNumber(): string
    {
        $year = now()->format('Y');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $sequence = str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
            $reference = "REQ-{$year}-{$sequence}";

            if (! DocumentRequest::query()->where('reference_no', $reference)->exists()) {
                return $reference;
            }
        }

        throw new \RuntimeException('Unable to generate a unique request reference number.');
    }
}
