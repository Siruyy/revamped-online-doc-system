<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\CsvExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserExportController extends Controller
{
    public function __invoke(Request $request, CsvExportService $exports): StreamedResponse
    {
        $this->authorize('viewAny', User::class);

        $query = User::query()
            ->when($request->string('role')->toString(), fn ($q, $role) => $q->where('role', $role))
            ->when($request->string('status')->toString(), fn ($q, $status) => $q->where('status', $status))
            ->when($request->string('course')->toString(), fn ($q, $course) => $q->where('course', $course))
            ->when($request->filled('year'), fn ($q) => $q->where('year_level', (int) $request->input('year')))
            ->when($request->string('search')->toString(), function ($q, $search): void {
                $q->where(function ($inner) use ($search): void {
                    $inner->where('fullname', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('student_id', 'like', "%{$search}%");
                });
            })
            ->orderBy('id');

        return $exports->stream('users-export.csv', $query, [
            'ID', 'Full Name', 'Email', 'Role', 'Status', 'Course', 'Year', 'Created At', 'Approved At',
        ], fn (User $user): array => [
            $user->id,
            $user->fullname,
            $user->email,
            $user->role,
            $user->status,
            $user->course,
            $user->year_level,
            $user->created_at?->toDateTimeString(),
            $user->approved_at?->toDateTimeString(),
        ]);
    }
}
