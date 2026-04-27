<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\WithEagerLoading;
use App\Traits\WithQueryCache;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'tenant_id', 'two_factor_enabled', 'rate_limit_bypass'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable, WithEagerLoading, WithQueryCache;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'rate_limit_bypass' => 'boolean',
        ];
    }

    /**
     * Laratrust: User belongs to many roles.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    /**
     * Laratrust: User belongs to many permissions.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_user');
    }

    /**
     * Laratrust: User belongs to a tenant.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string|array $role): bool
    {
        if (is_array($role)) {
            return $this->roles()->whereIn('name', $role)->exists();
        }

        return $this->roles()->where('name', $role)->exists();
    }

    /**
     * Check if user has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return $this->roles()
            ->whereHas('permissions', fn ($q) => $q->where('name', $permission))
            ->exists();
    }

    /**
     * Assign a role to the user.
     */
    public function assignRole(string $role): self
    {
        $roleModel = Role::firstOrCreate(['name' => $role]);
        $this->roles()->syncWithoutDetaching([$roleModel->id]);

        return $this;
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(string $role): self
    {
        $this->roles()->detach(
            Role::where('name', $role)->pluck('id')
        );

        return $this;
    }

    /**
     * Sync multiple roles for the user.
     */
    public function syncRoles(array $roles): self
    {
        $roleIds = Role::whereIn('name', $roles)->pluck('id');
        $this->roles()->sync($roleIds);

        return $this;
    }

    /**
     * Check if user has 2FA enabled.
     */
    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_enabled && $this->two_factor_confirmed_at !== null;
    }

    /**
     * Enable 2FA for the user.
     */
    public function enableTwoFactor(string $secret, array $recoveryCodes): void
    {
        $this->update([
            'two_factor_enabled' => true,
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    /**
     * Disable 2FA for the user.
     */
    public function disableTwoFactor(): void
    {
        $this->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    /**
     * Get the 2FA secret.
     */
    public function getTwoFactorSecret(): ?string
    {
        return $this->two_factor_secret ? decrypt($this->two_factor_secret) : null;
    }

    /**
     * Get the recovery codes.
     */
    public function getRecoveryCodes(): array
    {
        return $this->two_factor_recovery_codes
            ? json_decode(decrypt($this->two_factor_recovery_codes), true)
            : [];
    }
}
