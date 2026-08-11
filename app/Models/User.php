<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'Admin';
    public const ROLE_DEVELOPER = 'Developer';
    public const ROLE_SALES_MANAGER = 'Sales Manager';
    public const ROLE_SALES_EXECUTIVE = 'Sales Executive';
    public const ROLE_MARKETING = 'Marketing';

    public const MANAGEABLE_ROLES = [
        self::ROLE_SALES_EXECUTIVE,
        self::ROLE_SALES_MANAGER,
        self::ROLE_ADMIN,
        self::ROLE_MARKETING,
    ];

    protected $fillable = [
        'name', 'email', 'password', 'phone', 'position',
        'avatar', 'role', 'status', 'target',
    ];

    protected $hidden = ['password', 'remember_token'];
    protected $casts  = ['password' => 'hashed'];

    // ── Relasi Sales ──
    public function leads(): HasMany      { return $this->hasMany(Lead::class, 'user_id'); }
    public function activities(): HasMany { return $this->hasMany(Activity::class, 'user_id'); }
    public function customers(): HasMany  { return $this->hasMany(Customer::class, 'user_id'); }

    // ── Role helpers ──
    /**
     * Developer adalah akun sistem tersembunyi dengan hak yang sama seperti Admin.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_ADMIN, self::ROLE_DEVELOPER], true);
    }

    public function isDeveloper(): bool      { return $this->role === self::ROLE_DEVELOPER; }
    public function isSalesManager(): bool   { return $this->role === self::ROLE_SALES_MANAGER; }
    public function isSalesExecutive(): bool { return $this->role === self::ROLE_SALES_EXECUTIVE; }

    public function scopeWithoutSystemAccounts(Builder $query): Builder
    {
        return $query->where('role', '!=', self::ROLE_DEVELOPER);
    }

    public function scopeAssignable(Builder $query): Builder
    {
        return $query->withoutSystemAccounts()->where('status', 'Active');
    }

    public function canAccess(string $feature): bool
    {
        return match($feature) {
            'settings'        => $this->isAdmin(),
            'users'           => $this->isAdmin() || $this->isSalesManager(),
            'reports'         => $this->isAdmin() || $this->isSalesManager(),
            'analytics'       => $this->isAdmin() || $this->isSalesManager(),
            'suppliers'       => $this->isAdmin() || $this->isSalesManager(),
            'purchase_orders' => $this->isAdmin() || $this->isSalesManager(),
            default           => true,
        };
    }

    public function getAvatarInitialsAttribute(): string
    {
        $parts = explode(' ', $this->name);
        return strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : substr($parts[0], 1, 1)));
    }
}
