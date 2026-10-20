<?php

if (! function_exists('asset_v')) {
    /**
     * URL d'un fichier de public/ suivie de sa date de modification (?v=...).
     * Le navigateur peut ainsi garder le fichier en cache longtemps : dès qu'il est modifié,
     * l'URL change et la nouvelle version est téléchargée.
     */
    function asset_v(string $chemin): string
    {
        $fichier = public_path($chemin);

        return asset($chemin).(is_file($fichier) ? '?v='.filemtime($fichier) : '');
    }
}
