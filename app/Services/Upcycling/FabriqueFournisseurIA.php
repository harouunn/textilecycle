<?php

namespace App\Services\Upcycling;

class FabriqueFournisseurIA
{
    public function creer(): FournisseurIA
    {
        if (config('upcycling.provider') !== 'ollama') {
            throw new AssistantIndisponible('Choisissez le fournisseur local Ollama. Le formulaire manuel reste disponible.', 'invalid_configuration');
        }

        return app(ClientOllama::class);
    }
}
