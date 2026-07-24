<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicProgram extends Model
{
    protected $fillable = [
        'academic_department_id',
        'code',
        'name',
        'major',
        'level',
        'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * @return BelongsTo<AcademicDepartment, $this>
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(AcademicDepartment::class, 'academic_department_id');
    }

    public function displayName(): string
    {
        return $this->major ? "{$this->name} — {$this->major}" : $this->name;
    }
}
