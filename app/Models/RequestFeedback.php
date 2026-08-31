<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestFeedback extends Model
{
    use HasFactory;

    protected $table = 'request_feedback';

    protected $fillable = [
        'document_request_id',
        'rating',
        'service_rating',
        'comments',
        'suggestions',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'service_rating' => 'integer',
            'submitted_at' => 'datetime',
        ];
    }

    public function documentRequest(): BelongsTo
    {
        return $this->belongsTo(DocumentRequest::class);
    }
}
