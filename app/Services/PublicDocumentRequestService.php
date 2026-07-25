<?php

namespace App\Services;

use App\Models\AcademicProgram;
use App\Models\DocumentRequest;
use App\Models\DocumentRequestItem;
use App\Models\DocumentType;
use App\Models\RequestRequirement;
use App\Models\User;
use App\Notifications\WorkflowStatusNotification;
use App\Support\PublicRequestOptions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicDocumentRequestService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{request: DocumentRequest, access_code: string}
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

            $isBasicEducation = $data['requester_division'] === 'basic_education';
            $hasWrongDivision = $documentTypes->contains(
                fn (DocumentType $type): bool => ($type->category === 'BasicEd') !== $isBasicEducation,
            );

            if ($hasWrongDivision) {
                throw new \RuntimeException('Selected documents do not match the school division.');
            }

            $program = $isBasicEducation
                ? null
                : AcademicProgram::query()->with('department')
                    ->whereKey($data['academic_program_id'])
                    ->where('is_active', true)
                    ->firstOrFail();
            $basicEducation = $isBasicEducation
                ? PublicRequestOptions::BASIC_EDUCATION_LEVELS[$data['basic_education_level']]
                : null;
            $requesterCourse = $isBasicEducation ? $basicEducation['code'] : $program->code;
            $programSnapshot = $isBasicEducation ? $basicEducation['label'] : $program->displayName();
            $departmentCode = $isBasicEducation ? 'BEC' : $program->department->code;
            $resolvedItems = [];

            foreach ($items as $item) {
                /** @var DocumentType $type */
                $type = $documentTypes->get((int) $item['document_type_id']);
                $copies = max(1, (int) $item['copies']);
                $resolvedItems[] = [
                    'type' => $type,
                    'copies' => $copies,
                    'authentication_requested' => (bool) ($item['authentication_requested'] ?? false),
                    'documentary_stamp_requested' => (bool) ($item['documentary_stamp_requested'] ?? false),
                    'semester_requested' => $item['semester_requested'] ?? null,
                ];
            }

            /** @var DocumentType $primaryType */
            $primaryType = $resolvedItems[0]['type'];
            $accessCode = strtoupper(Str::random(10));

            $documentRequest = DocumentRequest::query()->create([
                'reference_no' => $this->generateReferenceNumber(),
                'user_id' => null,
                'requester_name' => $data['requester_name'],
                'requester_email' => $data['requester_email'] ?? null,
                'requester_contact_number' => $data['requester_contact_number'],
                'requester_student_id' => $data['requester_student_id'] ?? null,
                'requester_division' => $data['requester_division'],
                'basic_education_level' => $data['basic_education_level'] ?? null,
                'requester_course' => $requesterCourse,
                'academic_program_id' => $program?->id,
                'academic_program_snapshot' => $programSnapshot,
                'academic_department_code_snapshot' => $departmentCode,
                'requester_year_level' => $data['requester_year_level'] ?? null,
                'requester_graduation_or_last_sem' => PublicRequestOptions::attendanceLabel(
                    $data['requester_last_term_attended'],
                    $data['requester_last_year_attended'],
                ),
                'requester_last_term_attended' => $data['requester_last_term_attended'],
                'requester_last_year_attended' => $data['requester_last_year_attended'],
                'document_type_id' => $primaryType->id,
                'quantity' => array_sum(array_column($resolvedItems, 'copies')),
                'page_count' => null,
                'fee_snapshot' => 0,
                'status' => 'pending',
                'processing_stage' => 'not_started',
                'workflow_stage' => 'registrar_review',
                'intake_mode' => 'public',
                'purpose' => $data['purpose'] === 'Other official purpose'
                    ? 'Other official purpose: '.trim($data['purpose_other'])
                    : $data['purpose'],
                'requester_profile' => [
                    'birth_date' => $data['birth_date'],
                    'birth_place' => $data['birth_place'],
                    'sex' => $data['sex'],
                    'civil_status' => $data['civil_status'],
                    'citizenship' => $data['citizenship'],
                    'home_address' => $data['home_address'],
                    'father_name' => $data['father_name'] ?? null,
                    'mother_maiden_name' => $data['mother_maiden_name'] ?? null,
                    'parents_address' => $data['parents_address'] ?? null,
                    'guardian_name' => $data['guardian_name'] ?? null,
                    'guardian_address' => $data['guardian_address'] ?? null,
                    'education' => $data['education'],
                    'employment_status' => $data['employment_status'],
                    'company_name' => $data['company_name'] ?? null,
                    'company_address' => $data['company_address'] ?? null,
                ],
                'fulfillment_method' => $data['fulfillment_method'],
                'delivery_address' => $data['delivery_address'] ?? null,
                'is_proxy_request' => (bool) ($data['is_proxy_request'] ?? false),
                'tracking_access_hash' => hash('sha256', $accessCode),
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
                    'page_count_snapshot' => 1,
                    'fee_per_page_snapshot' => 0,
                    'line_total' => 0,
                    'authentication_requested' => $item['authentication_requested'],
                    'documentary_stamp_requested' => $item['documentary_stamp_requested'],
                    'semester_requested' => $item['semester_requested'],
                ]);
            }

            $requiredKeys = collect($resolvedItems)
                ->flatMap(fn (array $item): array => (array) $item['type']->requirements)
                ->merge(['photo_2x2', 'psa_birth_certificate'])
                ->when($data['civil_status'] === 'Married', fn ($keys) => $keys->push('marriage_certificate'))
                ->when((bool) ($data['is_proxy_request'] ?? false), fn ($keys) => $keys->push('authorization_letter')->push('spa'))
                ->unique()
                ->values()
                ->all();

            $this->seedSubmittedRequirements($documentRequest, $requiredKeys, (array) ($data['requirements'] ?? []));

            ActivityLogger::log(
                'public_request_submitted',
                "Public request {$documentRequest->reference_no} was submitted by {$documentRequest->requester_name}.",
                null,
                null,
                ['document_request_id' => $documentRequest->id]
            );

            Notification::send(
                User::query()->whereIn('role', ['admin', 'superadmin'])->where('status', 'active')->get(),
                new WorkflowStatusNotification([
                    'type' => 'request_submitted',
                    'title' => 'New public document request',
                    'message' => "{$documentRequest->requester_name} submitted a public document request.",
                    'document_request_id' => $documentRequest->id,
                ]),
            );
            Notification::route('mail', $documentRequest->requester_email)
                ->notify(new WorkflowStatusNotification([
                    'type' => 'public_request_received',
                    'title' => 'Your SVCI document request was received',
                    'message' => "Reference: {$documentRequest->reference_no}. Private access code: {$accessCode}. Save both; the access code is required for corrections, payment, and the claim slip.",
                    'url' => route('track-document', ['reference_no' => $documentRequest->reference_no]),
                ]));

            return ['request' => $documentRequest->refresh(), 'access_code' => $accessCode];
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
