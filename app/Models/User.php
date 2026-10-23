<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Staff account for the admissions office dashboard.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Role $role
 * @property bool $active
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role', 'active'];

    protected $hidden = ['password', 'remember_token'];

    #[\Override]
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    /** Admins can do everything; otherwise the role must be in the list. */
    public function hasRole(Role ...$roles): bool
    {
        return $this->isAdmin() || in_array($this->role, $roles, true);
    }

    public function canAdmissions(): bool
    {
        return $this->hasRole(Role::Admissions);
    }

    public function canCommunications(): bool
    {
        return $this->hasRole(Role::Communications);
    }

    /** The first page this person can use after signing in. */
    public function homeRoute(): string
    {
        return $this->role === Role::Communications ? route('admin.news.index') : route('admin.dashboard');
    }
}
