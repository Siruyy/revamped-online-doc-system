<?php

namespace App\Models;

use App\Support\ClearanceSignatories;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Clearance extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'document_request_id',
        'teacher_status',
        'teacher_remarks',
        'teacher_signed_by',
        'teacher_signed_at',
        'dean_status',
        'dean_remarks',
        'dean_signed_by',
        'dean_signed_at',
        'accounting_status',
        'accounting_remarks',
        'accounting_signed_by',
        'accounting_signed_at',
        'sao_status',
        'sao_remarks',
        'sao_signed_by',
        'sao_signed_at',
        'president_status',
        'president_remarks',
        'president_signed_by',
        'president_signed_at',
        'librarian_status',
        'librarian_remarks',
        'librarian_signed_by',
        'librarian_signed_at',
        'student_affairs_status',
        'student_affairs_remarks',
        'student_affairs_signed_by',
        'student_affairs_signed_at',
        'alumni_status',
        'alumni_remarks',
        'alumni_signed_by',
        'alumni_signed_at',
        'guidance_status',
        'guidance_remarks',
        'guidance_signed_by',
        'guidance_signed_at',
        'overall_status',
        'completed_at',
        'pdf_path',
        'uploaded_file_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'teacher_signed_at' => 'datetime',
            'dean_signed_at' => 'datetime',
            'accounting_signed_at' => 'datetime',
            'sao_signed_at' => 'datetime',
            'president_signed_at' => 'datetime',
            'librarian_signed_at' => 'datetime',
            'student_affairs_signed_at' => 'datetime',
            'alumni_signed_at' => 'datetime',
            'guidance_signed_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function documentRequest(): BelongsTo
    {
        return $this->belongsTo(DocumentRequest::class);
    }

    public function teacherSigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_signed_by');
    }

    public function deanSigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dean_signed_by');
    }

    public function accountingSigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accounting_signed_by');
    }

    public function saoSigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sao_signed_by');
    }

    public function presidentSigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'president_signed_by');
    }

    public function librarianSigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'librarian_signed_by');
    }

    public function studentAffairsSigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_affairs_signed_by');
    }

    public function alumniSigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'alumni_signed_by');
    }

    public function guidanceSigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guidance_signed_by');
    }

    public function isComplete(): bool
    {
        return collect(ClearanceSignatories::definitions())
            ->every(fn (array $signatory): bool => $this->{$signatory['status']} === 'cleared');
    }

    public function recomputeOverallStatus(): self
    {
        $statuses = collect(ClearanceSignatories::definitions())
            ->map(fn (array $signatory): mixed => $this->{$signatory['status']})
            ->all();

        if (in_array('denied', $statuses, true)) {
            $this->overall_status = 'denied';
            $this->completed_at = null;
        } elseif ($this->isComplete()) {
            $this->overall_status = 'completed';
            $this->completed_at ??= now();
        } else {
            $this->overall_status = 'in_progress';
            $this->completed_at = null;
        }

        return $this;
    }
}
