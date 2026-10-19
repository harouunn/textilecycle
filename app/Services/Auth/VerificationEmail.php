<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Notifications\CodeVerificationEmail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Vérification de l'adresse e-mail par code à 6 chiffres envoyé par e-mail.
 * Le code est haché en base, expire après 15 minutes et n'autorise que 5 essais.
 */
class VerificationEmail
{
    public const DUREE_MINUTES = 15;

    public const ESSAIS_MAX = 5;

    /** Clé de session : en développement sans serveur mail, le code est affiché à l'écran pour tester. */
    public const SESSION_CODE_DEV = 'verification.code_dev';

    /**
     * Génère un nouveau code, l'enregistre et l'envoie. Renvoie false si l'e-mail n'a pas pu partir.
     */
    public function envoyerCode(User $user): bool
    {
        $code = str_pad((string) random_int(0, 999_999), 6, '0', STR_PAD_LEFT);

        $user->forceFill([
            'code_verification' => Hash::make($code),
            'code_verification_expire_le' => now()->addMinutes(self::DUREE_MINUTES),
            'code_verification_essais' => 0,
        ])->save();

        if ($this->modeDeveloppement()) {
            session()->put(self::SESSION_CODE_DEV, $code);
        }

        try {
            $user->notify(new CodeVerificationEmail($code));
        } catch (Throwable $e) {
            Log::error('Envoi du code de vérification impossible.', ['email' => $user->email, 'erreur' => $e->getMessage()]);

            return false;
        }

        return true;
    }

    /**
     * @return 'ok'|'incorrect'|'expire'|'bloque'
     */
    public function verifier(User $user, ?string $code): string
    {
        if ($user->code_verification === null || $user->code_verification_expire_le?->isPast()) {
            return 'expire';
        }
        if ($user->code_verification_essais >= self::ESSAIS_MAX) {
            return 'bloque';
        }

        if (! Hash::check(preg_replace('/\D/', '', (string) $code), $user->code_verification)) {
            $user->increment('code_verification_essais');

            return 'incorrect';
        }

        $user->forceFill([
            'email_verified_at' => now(),
            'code_verification' => null,
            'code_verification_expire_le' => null,
            'code_verification_essais' => 0,
        ])->save();
        session()->forget(self::SESSION_CODE_DEV);
        event(new Verified($user));

        return 'ok';
    }

    /**
     * En local, quand les e-mails ne partent pas vraiment (mailer « log »), on montre le code pour pouvoir tester.
     */
    public function modeDeveloppement(): bool
    {
        return app()->isLocal() && config('mail.default') === 'log';
    }
}
