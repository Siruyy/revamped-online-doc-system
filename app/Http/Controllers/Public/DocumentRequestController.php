<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StorePublicDocumentRequest;
use App\Models\DocumentType;
use App\Models\PaymentProfile;
use App\Services\Policy\RequestRulesEngine;
use App\Services\PublicDocumentRequestService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DocumentRequestController extends Controller
{
    public function create(RequestRulesEngine $rules): Response
    {
        $documentTypes = DocumentType::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get([
                'id', 'code', 'name', 'description', 'category', 'fee',
                'fee_formula', 'default_page_count', 'processing_days',
                'submission_window', 'release_channel', 'offices', 'requirements', 'flags',
            ])
            ->map(function (DocumentType $type) use ($rules): array {
                $spec = $rules->rulesFor($type);

                return [
                    'id' => $type->id,
                    'code' => $type->code,
                    'name' => $type->name,
                    'description' => $type->description,
                    'category' => $type->category,
                    'fee' => (float) $type->fee,
                    'fee_formula' => $type->fee_formula,
                    'default_page_count' => max(1, (int) ($type->default_page_count ?: 1)),
                    'sla_days' => $type->processing_days,
                    'submission_window' => $type->submission_window,
                    'submission_window_label' => config('policy.release_channels.'.$type->submission_window, $type->submission_window),
                    'release_channel' => $type->release_channel,
                    'release_channel_label' => config('policy.release_channels.'.$type->release_channel, $type->release_channel),
                    'offices' => collect($spec['offices'])
                        ->map(fn (string $key): array => [
                            'key' => $key,
                            'label' => config('policy.offices.'.$key.'.label', $key),
                        ])->values(),
                    'requirements' => collect($spec['requirements'])
                        ->map(fn (string $key): array => [
                            'key' => $key,
                            'label' => config('policy.requirements.'.$key.'.label', $key),
                            'hint' => config('policy.requirements.'.$key.'.hint'),
                        ])->values(),
                    'flags' => $spec['flags'],
                ];
            })
            ->groupBy('category');

        $paymentProfile = PaymentProfile::active();

        return Inertia::render('Public/RequestDocument', [
            'documentTypeGroups' => $documentTypes,
            'paymentProfile' => $paymentProfile ? [
                'bank_name' => $paymentProfile->bank_name,
                'account_name' => $paymentProfile->account_name,
                'account_number' => $paymentProfile->account_number,
                'qr_url' => $paymentProfile->qr_path
                    ? route('public.files.payment-qr', $paymentProfile->id)
                    : null,
                'instructions' => $paymentProfile->instructions,
            ] : null,
            'requirementsCatalog' => config('policy.requirements'),
            'releaseChannels' => config('policy.release_channels'),
        ]);
    }

    public function store(StorePublicDocumentRequest $request, PublicDocumentRequestService $service): RedirectResponse
    {
        try {
            $result = $service->create($request->validated());
        } catch (\Exception $exception) {
            report($exception);

            return back()->withErrors([
                'items' => 'We could not process your request. Please try again.',
            ])->withInput();
        }

        return redirect()->route('public.requests.submitted', $result['request']->reference_no);
    }

    public function submitted(string $reference): Response
    {
        return Inertia::render('Public/RequestSubmitted', [
            'reference' => $reference,
        ]);
    }
}
