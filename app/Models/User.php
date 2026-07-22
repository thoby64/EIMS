<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['public_id', 'staff_number', 'name', 'email', 'phone', 'status', 'organizational_unit_id', 'department_id', 'primary_location_id', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withPivot(['assigned_by', 'assigned_at']);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function maintainableCategories(): BelongsToMany
    {
        return $this->belongsToMany(AssetCategory::class, 'maintenance_category_responsibilities')
            ->wherePivot('responsibility', 'maintenance')
            ->withPivot(['responsibility', 'assigned_by_user_id', 'is_active', 'assigned_at'])
            ->withTimestamps();
    }

    public function reviewableMaintenanceCategories(): BelongsToMany
    {
        return $this->belongsToMany(AssetCategory::class, 'maintenance_category_responsibilities')
            ->wherePivot('responsibility', 'review')
            ->withPivot(['responsibility', 'assigned_by_user_id', 'is_active', 'assigned_at'])
            ->withTimestamps();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles()->whereHas('permissions', fn ($query) => $query->where('slug', $permission))->exists();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
