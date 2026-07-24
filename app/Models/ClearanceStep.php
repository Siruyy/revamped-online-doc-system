<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClearanceStep extends Model
{
    protected $fillable = [
        'clearance_id',
        'office_code',
        'label',
        'sequence',
        'department_code',
        'assigned_user_id',
        'status',
        'remarks',
        'signed_by',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'signed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Clearance, $this>
     */
    public function clearance(): BelongsTo
    {
        return $this->belongsTo(Clearance::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function signer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signed_by');
    }
}
