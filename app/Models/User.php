<?php

namespace App\Models;

use App\Enums\Role;
use App\Notifications\ReinitialisationMotDePasse;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\Traits\HasRoles;

// Le rôle n'est rempli que par le back office (UserRequest) : l'inscription ne transmet que nom, email et mot de passe.
#[Fillable(['name', 'email', 'role', 'password'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'code_verification'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'client',
    ];

    /**
     * Le rôle Spatie (et donc ses permissions) suit toujours la colonne users.role.
     */
    protected static function booted(): void
    {
        static::saved(function (User $user) {
            if ($user->wasRecentlyCreated || $user->wasChanged('role')) {
                $user->syncRoles([SpatieRole::findOrCreate($user->role->value, 'web')]);
            }
        });
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

    /**
     * Lien « Mot de passe oublié » envoyé par un e-mail en français.
     *
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ReinitialisationMotDePasse($token));
    }

    /**
     * URL de la photo de profil, ou null (les vues affichent alors les initiales).
     */
    public function photoProfilUrl(): ?string
    {
        return $this->photo_profil ? Storage::disk('public')->url($this->photo_profil) : null;
    }

    /**
     * Initiales affichées à la place de la photo (ex. « Alaa Tahri » → « AT »).
     */
    public function initiales(): string
    {
        return Str::upper(collect(preg_split('/\s+/', trim((string) $this->name)))
            ->filter()->take(2)->map(fn (string $mot) => Str::substr($mot, 0, 1))->implode('')) ?: '?';
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
