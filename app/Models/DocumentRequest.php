<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DocumentRequest extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'reference_no',
        'user_id',
        'requester_name',
        'requester_last_name',
        'requester_first_name',
        'requester_middle_name',
        'requester_suffix',
        'requester_email',
        'requester_contact_number',
        'requester_student_id',
        'requester_division',
        'basic_education_level',
        'requester_course',
        'academic_program_id',
        'academic_program_snapshot',
        'academic_department_code_snapshot',
        'requester_year_level',
        'requester_year_level_status',
        'requester_graduation_or_last_sem',
        'requester_last_term_attended',
        'requester_last_year_attended',
        'document_type_id',
        'quantity',
        'page_count',
        'fee_snapshot',
        'status',
        'processing_stage',
        'workflow_stage',
        'intake_mode',
        'denial_reason',
        'approved_by',
        'approved_at',
        'released_at',
        'purpose',
        'extra_data',
        'requester_profile',
        'requester_claimant_name',
        'representative_relationship',
        'owner_residence',
        'fulfillment_method',
        'delivery_address',
        'delivery_provider',
        'courier_name',
        'courier_tracking_number',
        'is_proxy_request',
        'tracking_access_hash',
        'shipping_fee',
        'quote_total',
        'quote_notes',
        'evaluated_by',
        'evaluated_at',

        'sla_start_at',
        'sla_paused_at',
        'sla_resumed_at',
        'sla_pause_reason',
        'expected_release_on',
        'release_channel',

        'requires_hd_return',
        'hd_received_at',

        'transfer_exception_requested',
        'transfer_exception_approved',
        'transfer_exception_decided_at',

        'payment_verified_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'approved_at' => 'datetime',
            'released_at' => 'datetime',
            'sla_start_at' => 'datetime',
            'sla_paused_at' => 'datetime',
            'sla_resumed_at' => 'datetime',
            'expected_release_on' => 'date',
            'hd_received_at' => 'datetime',
            'transfer_exception_decided_at' => 'datetime',
            'payment_verified_at' => 'datetime',
            'evaluated_at' => 'datetime',
            'requires_hd_return' => 'boolean',
            'transfer_exception_requested' => 'boolean',
            'transfer_exception_approved' => 'boolean',
            'requester_year_level' => 'integer',
            'fee_snapshot' => 'decimal:2',
            'extra_data' => 'array',
            'requester_profile' => 'array',
            'is_proxy_request' => 'boolean',
            'shipping_fee' => 'decimal:2',
            'quote_total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $documentRequest): void {
            if ($documentRequest->reference_no) {
                return;
            }

            $year = now()->format('Y');
            $sequence = str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
            $documentRequest->reference_no = "REQ-{$year}-{$sequence}";
        });
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<DocumentType, $this>
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    /**
     * @return BelongsTo<AcademicProgram, $this>
     */
    public function academicProgram(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<Clearance, $this>
     */
    public function clearances(): HasMany
    {
        return $this->hasMany(Clearance::class);
    }

    /**
     * @return HasMany<RequestRequirement, $this>
     */
    public function requirements(): HasMany
    {
        return $this->hasMany(RequestRequirement::class);
    }

    /**
     * @return HasOne<ClaimSlip, $this>
     */
    public function claimSlip(): HasOne
    {
        return $this->hasOne(ClaimSlip::class);
    }

    /**
     * @return HasOne<RequestFeedback, $this>
     */
    public function feedback(): HasOne
    {
        return $this->hasOne(RequestFeedback::class);
    }

    /**
     * @return HasMany<DocumentRequestItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(DocumentRequestItem::class);
    }

    /**
     * Total fee across all items (falls back to fee_snapshot for legacy requests).
     */
    public function totalFee(): Attribute
    {
        return Attribute::make(
            get: function (): float {
                if ($this->relationLoaded('items') && $this->items->isNotEmpty()) {
                    return (float) $this->items->sum('line_total');
                }

                return (float) ($this->fee_snapshot ?? 0);
            },
        );
    }

    /**
     * Whether the student can upload a payment receipt (request must be approved first).
     */
    public function paymentUploadUnlocked(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => in_array($this->status, ['approved', 'completed'], true),
        );
    }

    public function isOpenForRegistrarDenial(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        if ($this->intake_mode !== 'public') {
            return true;
        }

        return $this->quote_total === null
            && in_array($this->workflow_stage, ['submitted', 'registrar_review'], true);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeByUser(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $query->where('user_id', $userId);
    }

    /**
     * True when every required requirement has been validated.
     */
    public function allRequirementsValidated(): bool
    {
        return $this->requirements()->where('status', '!=', 'validated')->doesntExist();
    }
}
