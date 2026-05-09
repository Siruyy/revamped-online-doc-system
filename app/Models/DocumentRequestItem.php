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
    ];

    protected function casts(): array
    {
        return [
            'copies' => 'integer',
            'page_count_snapshot' => 'integer',
            'fee_per_page_snapshot' => 'decimal:2',
            'line_total' => 'decimal:2',
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
