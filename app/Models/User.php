<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'fullname',
        'email',
        'password',
        'role',
        'status',
        'course',
        'year_level',
        'student_id',
        'contact_number',
        'avatar_path',
        'signature_path',
        'approved_by',
        'approved_at',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function documentRequests(): HasMany
    {
        return $this->hasMany(DocumentRequest::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function clearances(): HasMany
    {
        return $this->hasMany(Clearance::class);
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    /**
     * Clearance signatures by the current user's department role.
     */
    public function signedClearances(): HasMany
    {
        $foreignKey = match ($this->role) {
            'teacher' => 'teacher_signed_by',
            'dean' => 'dean_signed_by',
            'accounting' => 'accounting_signed_by',
            'sao' => 'sao_signed_by',
            default => 'teacher_signed_by',
        };

        return $this->hasMany(Clearance::class, $foreignKey);
    }

    public function scopeStudents(Builder $query): Builder
    {
        return $query->where('role', 'student');
    }

    public function scopeStaff(Builder $query): Builder
    {
        return $query->whereIn('role', ['admin', 'teacher', 'dean', 'accounting', 'sao', 'superadmin']);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isDepartment(): bool
    {
        return in_array($this->role, ['teacher', 'dean', 'accounting', 'sao'], true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function getNameAttribute(): string
    {
        return $this->fullname;
    }

    public function setNameAttribute(string $value): void
    {
        $this->attributes['fullname'] = $value;
    }
}
