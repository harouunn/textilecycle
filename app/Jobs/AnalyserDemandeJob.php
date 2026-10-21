<?php

namespace App\Jobs;

use App\Models\DemandeReparation;
use App\Services\Ateliers\IA\AnalysisService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AnalyserDemandeJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public DemandeReparation $demande,
        public bool $forceReanalyse = false
    ) {
        $this->onQueue('database');
    }

    public function handle(AnalysisService $analysisService): void
    {
        $analysisService->analyser($this->demande, $this->forceReanalyse);
    }
}
