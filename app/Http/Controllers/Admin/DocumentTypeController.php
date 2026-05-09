<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocumentTypeController extends Controller
{
    public function index(): Response
    {
        $types = DocumentType::query()
            ->withCount('documentRequests')
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/DocumentTypes/Index', [
            'documentTypes' => $types,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', 'max:120'],
            'fee' => ['required', 'numeric', 'min:0'],
            'default_page_count' => ['required', 'integer', 'min:1', 'max:500'],
            'processing_days' => ['required', 'integer', 'min:1', 'max:365'],
            'is_active' => ['required', 'boolean'],
        ]);

        DocumentType::query()->create(array_merge($validated, ['fee_formula' => 'per_page']));

        ActivityLogger::log(
            'document_type_created',
            "Admin {$request->user()->email} created document type {$validated['name']}.",
            $request->user()
        );

        return back()->with('status', 'Document type created successfully.');
    }

    public function update(Request $request, DocumentType $documentType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string', 'max:120'],
            'fee' => ['required', 'numeric', 'min:0'],
            'default_page_count' => ['required', 'integer', 'min:1', 'max:500'],
            'processing_days' => ['required', 'integer', 'min:1', 'max:365'],
            'is_active' => ['required', 'boolean'],
        ]);

        $documentType->update(array_merge($validated, ['fee_formula' => 'per_page']));

        ActivityLogger::log(
            'document_type_updated',
            "Admin {$request->user()->email} updated document type {$documentType->name}.",
            $request->user()
        );

        return back()->with('status', 'Document type updated successfully.');
    }

    public function destroy(DocumentType $documentType): RedirectResponse
    {
        if ($documentType->documentRequests()->exists()) {
            $documentType->update(['is_active' => false]);

            ActivityLogger::log(
                'document_type_disabled',
                "Admin ".request()->user()->email." disabled document type {$documentType->name}.",
                request()->user()
            );

            return back()->with('status', 'Document type has existing requests and was disabled instead of deleted.');
        }

        $documentType->delete();

        ActivityLogger::log(
            'document_type_deleted',
            "Admin ".request()->user()->email." deleted document type {$documentType->name}.",
            request()->user()
        );

        return back()->with('status', 'Document type deleted successfully.');
    }
}
