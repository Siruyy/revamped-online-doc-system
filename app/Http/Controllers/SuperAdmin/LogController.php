<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LogController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ActivityLog::class);

        $logs = ActivityLog::query()
            ->with(['user:id,fullname,email', 'affectedUser:id,fullname,email'])
            ->when($request->string('action')->toString(), fn ($q, $action) => $q->where('action', $action))
            ->when($request->integer('user_id'), fn ($q, $userId) => $q->where('user_id', $userId))
            ->when($request->integer('affected_user_id'), fn ($q, $id) => $q->where('affected_user_id', $id))
            ->when($request->date('from'), fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($request->date('to'), fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->when($request->string('q')->toString(), function ($q, $search) {
                $like = '%'.$search.'%';
                $q->where(function ($inner) use ($like) {
                    $inner->where('description', 'like', $like)
                        ->orWhere('action', 'like', $like);
                });
            })
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('SuperAdmin/Logs/Index', [
            'logs' => $logs,
            'filters' => [
                'action' => $request->string('action')->toString(),
                'user_id' => $request->integer('user_id') ?: null,
                'affected_user_id' => $request->integer('affected_user_id') ?: null,
                'from' => $request->input('from'),
                'to' => $request->input('to'),
                'q' => $request->string('q')->toString(),
            ],
        ]);
    }
}
