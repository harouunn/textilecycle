<?php

namespace App\Services\Upcycling;

interface FournisseurIA
{
    public function generer(string $consigne, array $entree, array $schema): array;
}
