<?php

namespace App\Services\Auth;

use App\Models\User;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Double authentification par code temporaire (TOTP, RFC 6238), compatible Google Authenticator,
 * Microsoft Authenticator, Authy… L'utilisateur scanne un QR code une fois, puis saisit à chaque
 * connexion le code à 6 chiffres affiché par son application (renouvelé toutes les 30 secondes).
 * Des codes de secours à usage unique permettent de se connecter si le téléphone est perdu.
 */
class DoubleAuthentification
{
    private const NOMBRE_CODES_SECOURS = 8;

    /** Tolérance d'une période de 30 s avant/après, pour les horloges légèrement décalées. */
    private const FENETRE = 1;

    public function __construct(private Google2FA $google2fa) {}

    public function genererSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    /**
     * QR code (SVG) à scanner avec l'application d'authentification.
     */
    public function qrCodeSvg(User $user, string $secret, int $taille = 220): string
    {
        $url = $this->google2fa->getQRCodeUrl(config('app.name', 'TexTileCycle'), $user->email, $secret);
        $writer = new Writer(new ImageRenderer(new RendererStyle($taille, 1), new SvgImageBackEnd));

        return $writer->writeString($url);
    }

    public function codeValide(string $secret, ?string $code): bool
    {
        $code = preg_replace('/\s+/', '', (string) $code);

        return preg_match('/^\d{6}$/', $code) === 1
            && $this->google2fa->verifyKey($secret, $code, self::FENETRE);
    }

    /**
     * @return list<string> ex. ["K7Q2-M9XA", …]
     */
    public function genererCodesSecours(): array
    {
        return collect(range(1, self::NOMBRE_CODES_SECOURS))
            ->map(fn () => Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4)))
            ->all();
    }

    /**
     * Consomme un code de secours : il est retiré de la liste et ne pourra plus servir.
     */
    public function utiliserCodeSecours(User $user, ?string $code): bool
    {
        $code = Str::upper(trim((string) $code));
        $codes = $user->two_factor_recovery_codes ?? [];

        if ($code === '' || ! in_array($code, $codes, true)) {
            return false;
        }

        $user->forceFill(['two_factor_recovery_codes' => array_values(array_diff($codes, [$code]))])->save();

        return true;
    }
}
