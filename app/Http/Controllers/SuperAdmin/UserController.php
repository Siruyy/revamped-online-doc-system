<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdmin\BulkApproveUsersRequest;
use App\Http\Requests\SuperAdmin\BulkDestroyUsersRequest;
use App\Http\Requests\SuperAdmin\StoreStaffUserRequest;
use App\Http\Requests\SuperAdmin\UpdateSuperAdminUserRequest;
use App\Models\User;
use App\Notifications\RegistrationApprovedNotification;
use App\Notifications\RegistrationRejectedNotification;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->when($request->string('role')->toString(), fn ($q, $role) => $q->where('role', $role))
            ->when($request->string('status')->toString(), fn ($q, $status) => $q->where('status', $status))
            ->when($request->string('course')->toString(), fn ($q, $course) => $q->where('course', $course))
            ->when($request->filled('year'), fn ($q) => $q->where('year_level', (int) $request->input('year')))
            ->when($request->string('search')->toString(), function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('fullname', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('student_id', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('SuperAdmin/Users/Index', [
            'users' => $users,
            'filters' => [
                'role' => $request->string('role')->toString(),
                'status' => $request->string('status')->toString(),
                'course' => $request->string('course')->toString(),
                'year' => $request->input('year'),
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('SuperAdmin/Users/Create');
    }

    public function store(StoreStaffUserRequest $request): RedirectResponse
    {
        $plain = Str::password(24);

        $user = User::query()->create([
            'fullname' => $request->validated('fullname'),
            'email' => $request->validated('email'),
            'password' => Hash::make($plain),
            'role' => $request->validated('role'),
            'status' => 'active',
            'email_verified_at' => now(),
            'course' => null,
            'year_level' => null,
            'student_id' => null,
            'contact_number' => null,
        ]);

        ActivityLogger::log(
            'staff_created',
            "SuperAdmin {$request->user()->email} created staff account {$user->email} ({$user->role}).",
            $request->user(),
            $user,
            ['role' => $user->role]
        );

        Password::sendResetLink(['email' => $user->email]);

        return redirect()
            ->route('superadmin.users.index')
            ->with('status', 'Staff account created. A password reset link was emailed to '.$user->email.'.');
    }

    public function pending(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('SuperAdmin/Users/Pending', [
            'users' => User::query()
                ->where('role', 'student')
                ->where('status', 'pending')
                ->latest()
                ->get(['id', 'fullname', 'email', 'course', 'year_level', 'student_id', 'created_at']),
        ]);
    }

    public function edit(User $user): Response
    {
        $this->authorize('view', $user);
        $this->authorize('update', $user);

        return Inertia::render('SuperAdmin/Users/Edit', [
            'user' => $user->only([
                'id', 'fullname', 'email', 'role', 'status', 'course', 'year_level',
                'student_id', 'contact_number', 'email_verified_at', 'created_at',
            ]),
        ]);
    }

    public function update(UpdateSuperAdminUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $data = $request->validated();

        $this->assertCanChangeActiveSuperAdmin($user, $data['role'] ?? null, $data['status'] ?? null);

        if (($data['role'] ?? $user->role) !== 'student') {
            $data['course'] = null;
            $data['year_level'] = null;
            $data['student_id'] = null;
        }

        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        ActivityLogger::log(
            'user_updated_by_superadmin',
            "SuperAdmin {$request->user()->email} updated user {$user->email}.",
            $request->user(),
            $user,
            ['changes' => $user->getChanges()]
        );

        return redirect()
            ->route('superadmin.users.edit', $user)
            ->with('status', 'User updated.');
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        $this->authorize('approve', $user);

        $user->update([
            'status' => 'active',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $user->notify(new RegistrationApprovedNotification);

        ActivityLogger::log(
            'registration_approved',
            "SuperAdmin {$request->user()->email} approved {$user->email}.",
            $request->user(),
            $user
        );

        return back()->with('status', 'User approved successfully.');
    }

    public function bulkApprove(BulkApproveUsersRequest $request): RedirectResponse
    {
        $ids = $request->validated('user_ids');
        $approved = 0;

        foreach ($ids as $id) {
            $user = User::query()->find($id);

            if (! $user || ! $request->user()->can('approve', $user)) {
                continue;
            }
            $user->update([
                'status' => 'active',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
            ]);
            $user->notify(new RegistrationApprovedNotification);
            ActivityLogger::log(
                'registration_approved',
                "SuperAdmin {$request->user()->email} approved {$user->email} (bulk).",
                $request->user(),
                $user
            );
            $approved++;
        }

        return back()->with('status', $approved.' user(s) approved.');
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        $this->authorize('reject', $user);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $user->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $user->notify(new RegistrationRejectedNotification($validated['reason'] ?? null));

        ActivityLogger::log(
            'registration_rejected',
            "SuperAdmin {$request->user()->email} rejected {$user->email}.",
            $request->user(),
            $user,
            ['reason' => $validated['reason'] ?? null]
        );

        return back()->with('status', 'User rejected successfully.');
    }

    public function suspend(Request $request, User $user): RedirectResponse
    {
        $this->authorize('suspend', $user);

        $this->assertNotLastActiveSuperAdmin($user, 'suspend');

        $user->update(['status' => 'suspended']);

        ActivityLogger::log(
            'user_suspended',
            "SuperAdmin {$request->user()->email} suspended {$user->email}.",
            $request->user(),
            $user
        );

        return back()->with('status', 'User suspended.');
    }

    public function reactivate(Request $request, User $user): RedirectResponse
    {
        $this->authorize('reactivate', $user);

        $user->update(['status' => 'active']);

        ActivityLogger::log(
            'user_reactivated',
            "SuperAdmin {$request->user()->email} reactivated {$user->email}.",
            $request->user(),
            $user
        );

        return back()->with('status', 'User reactivated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $this->assertNotLastActiveSuperAdmin($user, 'delete');

        $email = $user->email;

        ActivityLogger::log(
            'user_soft_deleted',
            "SuperAdmin {$request->user()->email} soft-deleted {$email}.",
            $request->user(),
            $user
        );

        $user->delete();

        return redirect()
            ->route('superadmin.users.index')
            ->with('status', 'User deleted.');
    }

    public function bulkDestroy(BulkDestroyUsersRequest $request): RedirectResponse
    {
        $ids = $request->validated('user_ids');
        $deleted = 0;

        foreach ($ids as $id) {
            if ((int) $id === $request->user()->id) {
                throw ValidationException::withMessages([
                    'user_ids' => 'You cannot delete your own account in a bulk operation.',
                ]);
            }

            $user = User::query()->find($id);

            if (! $user || ! $request->user()->can('delete', $user)) {
                continue;
            }

            $this->assertNotLastActiveSuperAdmin($user, 'delete');

            $email = $user->email;

            ActivityLogger::log(
                'user_soft_deleted',
                "SuperAdmin {$request->user()->email} soft-deleted {$email} (bulk).",
                $request->user(),
                $user
            );

            $user->delete();
            $deleted++;
        }

        return back()->with('status', $deleted.' user(s) deleted.');
    }

    private function assertNotLastActiveSuperAdmin(User $target, string $action): void
    {
        if ($target->role !== 'superadmin' || $target->status !== 'active') {
            return;
        }

        $others = User::query()
            ->where('role', 'superadmin')
            ->where('status', 'active')
            ->where('id', '!=', $target->id)
            ->count();

        if ($others < 1) {
            throw ValidationException::withMessages([
                'user' => "You cannot {$action} the only active SuperAdmin account.",
            ]);
        }
    }

    private function assertCanChangeActiveSuperAdmin(User $target, ?string $newRole, ?string $newStatus): void
    {
        if ($target->role !== 'superadmin' || $target->status !== 'active') {
            return;
        }

        $roleChange = $newRole !== null && $newRole !== 'superadmin';
        $statusChange = $newStatus !== null && $newStatus !== 'active';

        if (! $roleChange && ! $statusChange) {
            return;
        }

        $others = User::query()
            ->where('role', 'superadmin')
            ->where('status', 'active')
            ->where('id', '!=', $target->id)
            ->count();

        if ($others < 1) {
            throw ValidationException::withMessages([
                'role' => 'Cannot change the only active SuperAdmin role or status.',
            ]);
        }
    }
}
