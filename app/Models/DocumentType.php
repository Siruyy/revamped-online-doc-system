<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentType extends Model
{
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'code',
        'name',
        'description',
        'category',
        'fee',
        'fee_formula',
        'default_page_count',
        'processing_days',
        'submission_window',
        'release_channel',
        'offices',
        'requirements',
        'flags',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'fee' => 'decimal:2',
            'is_active' => 'boolean',
            'processing_days' => 'integer',
            'default_page_count' => 'integer',
            'offices' => 'array',
            'requirements' => 'array',
            'flags' => 'array',
        ];
    }

    public function documentRequests(): HasMany
    {
        return $this->hasMany(DocumentRequest::class);
    }

    public function documentRequestItems(): HasMany
    {
        return $this->hasMany(DocumentRequestItem::class);
    }

    public function hasFlag(string $flag): bool
    {
        return in_array($flag, (array) ($this->flags ?? []), true);
    }

    public function requiresClearance(): bool
    {
        return ! $this->hasFlag('no_clearance_needed');
    }
}
