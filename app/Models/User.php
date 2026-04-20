<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use App\Services\EmailOtpService;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password', 'role', 'phone', 'address', 'is_member', 'member_joined_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Replace Laravel's link-based email verification with our OTP flow.
     * Triggered via the Registered event listener + manual resend endpoint.
     */
    public function sendEmailVerificationNotification(): void
    {
        try {
            app(EmailOtpService::class)->send($this->email, EmailOtp::PURPOSE_EMAIL_VERIFICATION);
        } catch (\RuntimeException) {
            // Cooldown active — user can manually resend from the verify page.
        }
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
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_member' => 'boolean',
            'member_joined_at' => 'date',
        ];
    }

    /**
     * Scope the query to nasabah users only.
     */
    public function scopeNasabah(Builder $query): Builder
    {
        return $query->where('role', UserRole::Nasabah);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isOwner(): bool
    {
        return $this->role === UserRole::Owner;
    }

    public function isNasabah(): bool
    {
        return $this->role === UserRole::Nasabah;
    }

    public function hasRole(UserRole $role): bool
    {
        return $this->role === $role;
    }

    public function balance(): HasOne
    {
        return $this->hasOne(Balance::class);
    }

    public function savingTransactions(): HasMany
    {
        return $this->hasMany(SavingTransaction::class);
    }

    public function balanceHistories(): HasMany
    {
        return $this->hasMany(BalanceHistory::class);
    }

    public function pointHistories(): HasMany
    {
        return $this->hasMany(PointHistory::class);
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
