<?php

namespace App\Http\Controllers\Dons\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiLog;
use App\Models\Don;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * « Journal IA » : appels à Gemini des fonctionnalités du module, taux de réussite et durée moyenne.
 */
class JournalIaController extends Controller
{
    use AuthorizesRequests;

    public function __invoke(Request $request): View
    {
        $this->authorize('viewAny', Don::class);

        $filtres = [
            'fonctionnalite' => array_key_exists((string) $request->query('fonctionnalite'), AiLog::FONCTIONNALITES) ? $request->query('fonctionnalite') : null,
            'succes' => in_array($request->query('succes'), ['0', '1'], true) ? $request->query('succes') : null,
        ];

        $parFonctionnalite = AiLog::query()
            ->select('fonctionnalite', DB::raw('count(*) as appels'), DB::raw('sum(case when succes then 1 else 0 end) as reussis'), DB::raw('avg(duree_ms) as duree_moyenne'))
            ->groupBy('fonctionnalite')
            ->get()
            ->keyBy('fonctionnalite');

        $appels = (int) $parFonctionnalite->sum('appels');
        $reussis = (int) $parFonctionnalite->sum('reussis');

        $journal = AiLog::query()
            ->with('user')
            ->when($filtres['fonctionnalite'], fn ($q, $f) => $q->where('fonctionnalite', $f))
            ->when($filtres['succes'] !== null, fn ($q) => $q->where('succes', $filtres['succes'] === '1'))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.dons.journal-ia', [
            'journal' => $journal,
            'filtres' => $filtres,
            'parFonctionnalite' => $parFonctionnalite,
            'appels' => $appels,
            'tauxReussite' => $appels > 0 ? round($reussis / $appels * 100, 1) : null,
            'iaConfiguree' => filled(config('services.gemini.key')),
        ]);
    }
}
