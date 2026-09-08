<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class PaymentProfile extends Model
{
    protected $fillable = [
        'bank_name',
        'account_name',
        'account_number',
        'qr_path',
        'instructions',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Return all active payment profiles for display to students.
     *
     * @return Collection<int, self>
     */
    public static function activeProfiles(): Collection
    {
        return self::query()->where('is_active', true)->latest()->get();
    }

    /**
     * Backward-compatible single-profile helper (returns the most recent active profile).
     */
    public static function active(): ?self
    {
        return self::query()->where('is_active', true)->latest()->first();
    }

    /** @return array<string, mixed> */
    public function publicDetails(): array
    {
        return [
            'bank_name' => $this->bank_name,
            'account_name' => $this->account_name,
            'account_number' => $this->account_number,
            'instructions' => $this->instructions,
            'qr_url' => $this->qr_path ? route('public.files.payment-qr', $this->id) : null,
        ];
    }
}
