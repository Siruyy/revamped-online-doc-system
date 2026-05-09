<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimSlip extends Model
{
    use HasFactory;

    protected $fillable = [
        'claim_number',
        'document_request_id',
        'user_id',
        'release_channel',
        'claim_date',
        'state',
        'claimant_name',
        'claimant_id_reference',
        'is_proxy_release',
        'authorization_type',
        'notes',
        'released_by',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'claim_date' => 'date',
            'released_at' => 'datetime',
            'is_proxy_release' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $slip): void {
            if ($slip->claim_number) {
                return;
            }

            $year = now()->format('Y');
            $sequence = str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
            $slip->claim_number = "CLS-{$year}-{$sequence}";
        });
    }

    public function documentRequest(): BelongsTo
    {
        return $this->belongsTo(DocumentRequest::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function releaser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }
}
