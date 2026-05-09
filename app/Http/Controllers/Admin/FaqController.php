<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Faqs/Index', [
            'faqs' => Faq::query()
                ->with('author:id,fullname')
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:student,staff,all'],
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['required', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $faq = Faq::query()->create([
            'author_id' => $request->user()->id,
            'role' => $validated['role'],
            'question' => $validated['question'],
            'answer' => $validated['answer'],
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        ActivityLogger::log(
            'faq_created',
            "Admin {$request->user()->email} created FAQ #{$faq->id}.",
            $request->user()
        );

        return back()->with('status', 'FAQ entry created successfully.');
    }

    public function update(Request $request, Faq $faq): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:student,staff,all'],
            'question' => ['required', 'string', 'max:500'],
            'answer' => ['required', 'string'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);

        $faq->update($validated);

        ActivityLogger::log(
            'faq_updated',
            "Admin {$request->user()->email} updated FAQ #{$faq->id}.",
            $request->user()
        );

        return back()->with('status', 'FAQ entry updated successfully.');
    }

    public function destroy(Request $request, Faq $faq): RedirectResponse
    {
        $faq->delete();

        ActivityLogger::log(
            'faq_deleted',
            "Admin {$request->user()->email} deleted FAQ #{$faq->id}.",
            $request->user()
        );

        return back()->with('status', 'FAQ entry deleted successfully.');
    }
}
