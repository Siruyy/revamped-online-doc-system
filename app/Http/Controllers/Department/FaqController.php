<?php

namespace App\Http\Controllers\Department;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Inertia\Inertia;
use Inertia\Response;

class FaqController extends Controller
{
    public function index(): Response
    {
        $faqs = Faq::query()
            ->whereIn('role', ['all', 'staff'])
            ->orderBy('sort_order')
            ->get(['id', 'question', 'answer']);

        return Inertia::render('Department/Faq', [
            'faqs' => $faqs,
        ]);
    }
}
