<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\Captcha;
use Illuminate\Http\Response;

class CaptchaController extends Controller
{
    /**
     * Image CAPTCHA : un nouveau code est créé à chaque affichage, jamais mis en cache.
     */
    public function __invoke(Captcha $captcha): Response
    {
        return response($captcha->genererImage(), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }
}
