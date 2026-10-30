<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use App\Http\Requests\Department\UpdateDocumentPriceRequest;
use App\Models\DocumentType;
use App\Services\DocumentPricingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DocumentPriceController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewPrices', DocumentType::class);

        return Inertia::render('Department/Prices/Index', [
            'documentTypes' => DocumentType::query()->orderBy('category')->orderBy('name')
                ->get(['id', 'name', 'category', 'fee', 'fee_formula', 'is_active']),
        ]);
    }

    public function update(UpdateDocumentPriceRequest $request, DocumentType $documentType, DocumentPricingService $pricing): RedirectResponse
    {
        $pricing->updateFee($documentType, (float) $request->validated('fee'), $request->user());

        return back()->with('status', 'Document price updated. The public price list now uses this rate.');
    }
}
