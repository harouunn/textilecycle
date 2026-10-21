<?php

namespace App\Http\Controllers\Dons;

use App\Http\Controllers\Controller;
use App\Services\Dons\BadgeService;
use App\Services\Dons\ImpactCalculator;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * « Mon impact » (points, badges, CO₂ et eau évités) et classement public des donateurs.
 */
class ImpactController extends Controller
{
    public function monImpact(Request $request, ImpactCalculator $impact, BadgeService $badges): View
    {
        $user = $request->user();

        return view('dons.impact.mon-impact', [
            'impact' => $impact->pourUtilisateur($user),
            'points' => $badges->points($user),
            'badges' => $badges->badges($user),
        ]);
    }

    public function classement(BadgeService $badges): View
    {
        return view('dons.impact.classement', ['classement' => $badges->classement()]);
    }
}
