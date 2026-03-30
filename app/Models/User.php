<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMINISTRATOR = 'administrator';

    public const ROLE_IT_SUPPORT = 'it_support';

    public const ROLE_NOC = 'noc';

    public const ROLE_KEUANGAN = 'keuangan';

    public const ROLE_TEKNISI = 'teknisi';

    public const ROLE_CS = 'cs';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'nickname',
        'last_login_at',
        'is_super_admin',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_super_admin' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_super_admin === true;
    }

    public function isAdmin(): bool
    {
        return $this->isSuperAdmin() || $this->role === self::ROLE_ADMINISTRATOR;
    }

    public function roleLabel(): string
    {
        return match ($this->role) {
            self::ROLE_ADMINISTRATOR => 'Administrator',
            self::ROLE_IT_SUPPORT => 'IT Support',
            self::ROLE_NOC => 'NOC',
            self::ROLE_KEUANGAN => 'Keuangan',
            self::ROLE_TEKNISI => 'Teknisi',
            self::ROLE_CS => 'Customer Services',
            default => ucfirst(str_replace('_', ' ', (string) $this->role)),
        };
    }

    public function pushSubscriptions(): MorphMany
    {
        return $this->morphMany(PushSubscription::class, 'subscribable');
    }
}
