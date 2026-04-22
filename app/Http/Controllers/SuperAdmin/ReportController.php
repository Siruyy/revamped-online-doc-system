<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Clearance;
use App\Models\DocumentRequest;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $fromInput = $request->date('from');
        $toInput = $request->date('to');
        $from = $fromInput ? $fromInput->copy()->startOfDay() : now()->copy()->subDays(30)->startOfDay();
        $to = $toInput ? $toInput->copy()->endOfDay() : now()->copy()->endOfDay();

        $requestsByStatus = DocumentRequest::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->all();

        $paymentsByStatus = Payment::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->all();

        $clearancesByOverall = Clearance::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('overall_status, count(*) as c')
            ->groupBy('overall_status')
            ->pluck('c', 'overall_status')
            ->all();

        $registrations = User::query()
            ->where('role', 'student')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->all();

        return Inertia::render('SuperAdmin/Reports/Index', [
            'filters' => [
                'from' => $fromInput?->toDateString() ?? $from->toDateString(),
                'to' => $toInput?->toDateString() ?? $to->toDateString(),
            ],
            'requestsByStatus' => $requestsByStatus,
            'paymentsByStatus' => $paymentsByStatus,
            'clearancesByOverall' => $clearancesByOverall,
            'registrationsByStatus' => $registrations,
        ]);
    }
}
