<?php

namespace App\Models;

use App\Notifications\BrandedResetPasswordNotification;
use App\Support\ClearanceSignatories;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
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
        'academic_status',
        'transferred_at',
        'transferred_to',
        'is_nstp',
        'is_graduate',
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
            'transferred_at' => 'datetime',
            'is_nstp' => 'boolean',
            'is_graduate' => 'boolean',
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
        $foreignKey = ClearanceSignatories::isSignatoryRole($this->role)
            ? ClearanceSignatories::columns($this->role)['signed_by']
            : 'dean_signed_by';

        return $this->hasMany(Clearance::class, $foreignKey);
    }

    public function scopeStudents(Builder $query): Builder
    {
        return $query->where('role', 'student');
    }

    public function scopeStaff(Builder $query): Builder
    {
        return $query->whereIn('role', ['admin', ...ClearanceSignatories::roles(), 'superadmin']);
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
        return ClearanceSignatories::isSignatoryRole($this->role);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin';
    }

    public function isTransferred(): bool
    {
        return in_array($this->academic_status, ['transferred', 'dismissed'], true);
    }

    public function roleHomeRoute(): string
    {
        return match ($this->role) {
            'student' => 'student.dashboard',
            'admin' => 'admin.dashboard',
            'dean', 'president', 'librarian', 'student_affairs', 'alumni', 'guidance' => 'department.dashboard',
            'superadmin' => 'superadmin.dashboard',
            default => 'login',
        };
    }

    public function getNameAttribute(): string
    {
        return $this->fullname;
    }

    public function setNameAttribute(string $value): void
    {
        $this->attributes['fullname'] = $value;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new BrandedResetPasswordNotification($token));
    }

    /**
     * Private Echo channel for database / broadcast notifications.
     */
    public function receivesBroadcastNotificationsOn(): string
    {
        return 'user.'.$this->id;
    }
}
