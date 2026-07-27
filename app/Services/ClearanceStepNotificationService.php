<?php

namespace App\Services;

use App\Models\ClearanceStep;
use App\Models\User;
use App\Notifications\WorkflowStatusNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ClearanceStepNotificationService
{
    public function notifyActionable(ClearanceStep $step, bool $resubmitted = false): void
    {
        $step->loadMissing('clearance.documentRequest');

        if ($step->status !== 'pending' || ! $step->clearance?->documentRequest) {
            return;
        }

        $request = $step->clearance->documentRequest;
        $type = $resubmitted ? 'clearance_step_resubmitted' : 'clearance_step_actionable';
        $title = $resubmitted
            ? "Correction resubmitted for {$step->label}"
            : "Clearance ready for {$step->label}";
        $message = $resubmitted
            ? "A corrected document for request {$request->reference_no} is ready for another review."
            : "Request {$request->reference_no} is ready for your office's clearance review.";

        $this->recipientsFor($step)->each(function (User $recipient) use ($step, $request, $type, $title, $message): void {
            $recipient->notify(new WorkflowStatusNotification([
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'url' => $this->reviewUrl($recipient, $step),
                'clearance_id' => $step->clearance_id,
                'clearance_step_id' => $step->id,
                'document_request_id' => $request->id,
                'office_code' => $step->office_code,
            ]));
        });
    }

    /**
     * @return Collection<int, User>
     */
    private function recipientsFor(ClearanceStep $step): Collection
    {
        $officeRole = $step->office_code === 'registrar' ? 'admin' : $step->office_code;

        return User::query()
            ->where('status', 'active')
            ->where(function (Builder $query) use ($step, $officeRole): void {
                $query->where('role', 'superadmin')
                    ->orWhere(function (Builder $officeQuery) use ($step, $officeRole): void {
                        $officeQuery->where('role', $officeRole);

                        if ($step->assigned_user_id !== null) {
                            $officeQuery->whereKey($step->assigned_user_id);
                        }

                        if ($step->office_code === 'dean' && $step->department_code) {
                            $officeQuery->whereHas(
                                'academicDepartment',
                                fn (Builder $department): Builder => $department->where('code', $step->department_code),
                            );
                        }
                    });
            })
            ->get();
    }

    private function reviewUrl(User $recipient, ClearanceStep $step): string
    {
        $request = $step->clearance->documentRequest;

        return match ($recipient->role) {
            'superadmin' => route('superadmin.requests.show', $request),
            'admin' => route('admin.requests.show', $request),
            default => route('department.clearances.show', $step->clearance),
        };
    }
}
