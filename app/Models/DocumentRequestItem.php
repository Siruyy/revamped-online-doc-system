<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentRequestItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_request_id',
        'document_type_id',
        'copies',
        'page_count_snapshot',
        'fee_per_page_snapshot',
        'line_total',
        'authentication_requested',
        'documentary_stamp_requested',
        'semester_requested',
        'evaluated_page_count',
        'base_amount',
        'authentication_amount',
        'documentary_stamp_amount',
        'evaluation_notes',
    ];

    protected function casts(): array
    {
        return [
            'copies' => 'integer',
            'page_count_snapshot' => 'integer',
            'fee_per_page_snapshot' => 'decimal:2',
            'line_total' => 'decimal:2',
            'authentication_requested' => 'boolean',
            'documentary_stamp_requested' => 'boolean',
            'evaluated_page_count' => 'integer',
            'base_amount' => 'decimal:2',
            'authentication_amount' => 'decimal:2',
            'documentary_stamp_amount' => 'decimal:2',
        ];
    }

    public function documentRequest(): BelongsTo
    {
        return $this->belongsTo(DocumentRequest::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }
}
