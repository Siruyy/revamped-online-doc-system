<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\DocumentType;
use Inertia\Inertia;
use Inertia\Response;

class PriceListController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Public/PriceList', [
            'documentTypes' => DocumentType::query()
                ->where('is_active', true)
                ->orderBy('category')->orderBy('name')
                ->get(['id', 'code', 'name', 'category', 'fee', 'fee_formula']),
        ]);
    }
}
