<?php

namespace App\Http\Controllers\Admin\Depot;

use App\Http\Controllers\Controller;
use App\Services\Depot\StatistiquesDepot;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Statistiques propres au module Dépôt & catalogue : page du back office et rapport PDF.
 */
class StatistiqueController extends Controller
{
    public function __construct(private StatistiquesDepot $statistiques) {}

    public function index(): View
    {
        return view('admin.depot.statistiques.index', ['rapport' => $this->statistiques->rapport()]);
    }

    public function pdf(): Response
    {
        return Pdf::loadView('admin.depot.statistiques.pdf', ['rapport' => $this->statistiques->rapport()])
            ->setPaper('a4')
            ->download('statistiques-depot-'.now()->format('Y-m-d').'.pdf');
    }
}
