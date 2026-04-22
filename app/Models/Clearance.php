<?php

namespace App\Models;

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

    public function isComplete(): bool
    {
        return $this->teacher_status === 'cleared'
            && $this->dean_status === 'cleared'
            && $this->accounting_status === 'cleared'
            && $this->sao_status === 'cleared';
    }

    public function recomputeOverallStatus(): self
    {
        $statuses = [
            $this->teacher_status,
            $this->dean_status,
            $this->accounting_status,
            $this->sao_status,
        ];

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
