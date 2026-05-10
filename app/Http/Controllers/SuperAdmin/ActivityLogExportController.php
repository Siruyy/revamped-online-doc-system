<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\CsvExportService;
use DateTimeInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogExportController extends Controller
{
    public function __invoke(Request $request, CsvExportService $exports): StreamedResponse
    {
        $this->authorize('viewAny', ActivityLog::class);

        $query = ActivityLog::query()
            ->with(['user:id,fullname,email', 'affectedUser:id,fullname,email'])
            ->when($request->string('action')->toString(), fn ($q, $action) => $q->where('action', $action))
            ->when($request->integer('user_id'), fn ($q, $userId) => $q->where('user_id', $userId))
            ->when($request->integer('affected_user_id'), fn ($q, $id) => $q->where('affected_user_id', $id))
            ->when($request->date('from'), fn ($q, $from) => $q->whereDate('created_at', '>=', $from))
            ->when($request->date('to'), fn ($q, $to) => $q->whereDate('created_at', '<=', $to))
            ->when($request->string('q')->toString(), function ($q, $search): void {
                $like = '%'.$search.'%';
                $q->where(function ($inner) use ($like): void {
                    $inner->where('description', 'like', $like)
                        ->orWhere('action', 'like', $like);
                });
            })
            ->orderBy('id');

        return $exports->stream('activity-logs-export.csv', $query, [
            'ID', 'Action', 'Description', 'Actor', 'Actor Email', 'Affected User', 'Affected Email', 'IP Address', 'Created At',
        ], function (ActivityLog $log): array {
            $actor = $log->user instanceof User ? $log->user : null;
            $affectedUser = $log->affectedUser instanceof User ? $log->affectedUser : null;

            return [
                $log->id,
                $log->action,
                $log->description,
                $actor?->fullname,
                $actor?->email,
                $affectedUser?->fullname,
                $affectedUser?->email,
                $log->ip_address,
                $this->formatDate($log->created_at),
            ];
        });
    }

    private function formatDate(mixed $value): ?string
    {
        return $value instanceof DateTimeInterface ? $value->format('Y-m-d H:i:s') : null;
    }
}
