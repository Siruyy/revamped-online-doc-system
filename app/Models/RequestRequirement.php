<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_request_id',
        'requirement_key',
        'label',
        'status',
        'file_path',
        'notes',
        'validated_by',
        'validated_at',
    ];

    protected function casts(): array
    {
        return [
            'validated_at' => 'datetime',
        ];
    }

    public function documentRequest(): BelongsTo
    {
        return $this->belongsTo(DocumentRequest::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
