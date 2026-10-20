<?php

namespace App\Models;

use App\Enums\Role;
use App\Services\Auth\VerificationEmail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// Le rôle n'est rempli que par le back office (UserRequest) : l'inscription ne transmet que nom, email et mot de passe.
#[Fillable(['name', 'email', 'role', 'password'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'code_verification'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'client',
    ];

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
            'role' => Role::class,
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
            'code_verification_expire_le' => 'datetime',
        ];
    }

    /**
     * Vérification de l'e-mail par code à 6 chiffres (au lieu du lien de Laravel).
     * Appelée à l'inscription (événement Registered) et quand l'utilisateur demande un nouveau code.
     */
    public function sendEmailVerificationNotification(): void
    {
        app(VerificationEmail::class)->envoyerCode($this);
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    /**
     * La double authentification est active une fois le premier code confirmé.
     */
    public function aDoubleAuthentification(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    /**
     * Page d'arrivée après connexion : le back office pour un admin, le site public pour un client.
     */
    public function accueil(): string
    {
        return $this->isAdmin() ? route('admin.dashboard') : route('home');
    }

    /**
     * Le cycle du vêtement relie chaque compte à toutes les gestions du projet.
     *
     * @return HasMany<Vetement, $this>
     */
    public function vetements(): HasMany
    {
        return $this->hasMany(Vetement::class);
    }

    /**
     * @return HasMany<Reservation, $this>
     */
    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * @return HasMany<DemandeReparation, $this>
     */
    public function demandesReparation(): HasMany
    {
        return $this->hasMany(DemandeReparation::class);
    }

    /**
     * @return BelongsToMany<ProjetUpcycling, $this>
     */
    public function projetsFavoris(): BelongsToMany
    {
        return $this->belongsToMany(ProjetUpcycling::class, 'projet_upcycling_favoris', 'user_id', 'projet_upcycling_id')->withTimestamps();
    }

    /**
     * @return HasMany<ProjetUpcycling, $this>
     */
    public function projetsUpcycling(): HasMany
    {
        return $this->hasMany(ProjetUpcycling::class);
    }

    /**
     * @return HasMany<Don, $this>
     */
    public function dons(): HasMany
    {
        return $this->hasMany(Don::class);
    }
}
