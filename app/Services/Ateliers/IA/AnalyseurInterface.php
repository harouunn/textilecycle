<?php

namespace App\Services\Ateliers\IA;

interface AnalyseurInterface
{
    public function analyser(string $contenu): ?array;
}
