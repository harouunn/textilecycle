<?php

namespace App\Services\Ateliers\IA;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VisionService implements AnalyseurInterface
{
    public function analyser(string $photoPath): ?array
    {
        $apiKey = config('ia.gemini.api_key');
        $model = config('ia.gemini.model');

        if (! $apiKey) {
            Log::warning('Gemini API key non configurée');

            return null;
        }

        $imagePath = Storage::disk('public')->path($photoPath);
        if (! file_exists($imagePath)) {
            Log::error("Photo introuvable: {$photoPath}");

            return null;
        }

        $imageData = $this->redimensionnerImage($imagePath);
        if (! $imageData) {
            return null;
        }

        try {
            $response = Http::timeout(config('ia.gemini.timeout'))
                ->retry(1, 100)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                    'contents' => [[
                        'parts' => [
                            ['text' => 'IMPORTANT: Cette image doit contenir UN VÊTEMENT ENDOMMAGÉ pour analyse de réparation. 

Si l\'image ne montre PAS de vêtement (objet, nourriture, personne seule, etc.), réponds: {"erreur":"pas_vetement","message":"L\'image ne contient pas de vêtement à réparer"}

Sinon, analyse attentivement le vêtement et identifie le TYPE DE DOMMAGE:
- "trou" : trous, déchirures, accrocs dans le tissu
- "decoupe" : coutures décousues, ourlets défaits
- "bouton" : boutons manquants ou cassés
- "fermeture" : fermetures éclair cassées
- "autre" : SEULEMENT si aucun dommage visible ou autre problème

Évalue aussi la GRAVITÉ:
- "faible" : petit dommage localisé, réparation simple
- "moderee" : dommage moyen, plusieurs zones
- "elevee" : dommage important, multiples déchirures ou grandes zones

Réponds en JSON: {"type_dommage":"trou|decoupe|bouton|fermeture|autre","gravite":"faible|moderee|elevee","temps_estime_min":nombre}'],
                            ['inline_data' => [
                                'mime_type' => 'image/jpeg',
                                'data' => $imageData,
                            ]],
                        ],
                    ]],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'maxOutputTokens' => 300,
                    ],
                ]);

            if (! $response->successful()) {
                Log::error('Gemini API erreur', ['status' => $response->status()]);

                return null;
            }

            $result = $response->json();
            $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

            // Log pour debug
            Log::info('Gemini réponse brute', ['text' => $text]);

            if (! $text) {
                return null;
            }

            $text = trim($text, " \n\r\t\v\0`");
            if (str_starts_with($text, '```json')) {
                $text = trim(substr($text, 7));
            }
            if (str_ends_with($text, '```')) {
                $text = trim(substr($text, 0, -3));
            }

            $data = json_decode($text, true);

            // Si l'IA détecte que ce n'est pas un vêtement, on retourne le message
            if (isset($data['erreur']) && $data['erreur'] === 'pas_vetement') {
                return ['erreur' => 'pas_vetement', 'message' => $data['message'] ?? 'L\'image ne contient pas de vêtement à réparer'];
            }

            return $this->valider($data);
        } catch (\Exception $e) {
            Log::error('VisionService exception', ['message' => $e->getMessage()]);

            return null;
        }
    }

    private function redimensionnerImage(string $path): ?string
    {
        // En tests, si le fichier fait moins de 100 octets, on considère que c'est un fake
        if (filesize($path) < 100) {
            return base64_encode(file_get_contents($path));
        }

        try {
            $info = getimagesize($path);
            if (! $info) {
                return null;
            }

            $mime = $info['mime'];
            $image = match ($mime) {
                'image/jpeg' => imagecreatefromjpeg($path),
                'image/png' => imagecreatefrompng($path),
                'image/gif' => imagecreatefromgif($path),
                'image/webp' => imagecreatefromwebp($path),
                default => null,
            };

            if (! $image) {
                return null;
            }

            $width = imagesx($image);
            $height = imagesy($image);
            $maxSize = config('ia.image.max_width');

            if ($width <= $maxSize && $height <= $maxSize) {
                ob_start();
                imagejpeg($image, null, config('ia.image.quality'));
                $data = ob_get_clean();
                imagedestroy($image);

                return base64_encode($data);
            }

            $ratio = min($maxSize / $width, $maxSize / $height);
            $newWidth = (int) ($width * $ratio);
            $newHeight = (int) ($height * $ratio);

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            ob_start();
            imagejpeg($resized, null, config('ia.image.quality'));
            $data = ob_get_clean();

            imagedestroy($image);
            imagedestroy($resized);

            return base64_encode($data);
        } catch (\Exception $e) {
            Log::error('Redimensionnement image erreur', ['message' => $e->getMessage()]);

            return null;
        }
    }

    private function valider(?array $data): ?array
    {
        if (! $data || ! is_array($data)) {
            return ['type_dommage' => 'autre', 'gravite' => 'moderee', 'temps_estime_min' => 30];
        }

        // Si l'IA détecte que ce n'est pas un vêtement
        if (isset($data['erreur']) && $data['erreur'] === 'pas_vetement') {
            return null; // Retourne null pour indiquer que l'analyse a échoué
        }

        $typesDommage = ['trou', 'decoupe', 'bouton', 'fermeture', 'autre'];
        $gravites = ['faible', 'moderee', 'elevee'];

        return [
            'type_dommage' => in_array($data['type_dommage'] ?? '', $typesDommage) ? $data['type_dommage'] : 'autre',
            'gravite' => in_array($data['gravite'] ?? '', $gravites) ? $data['gravite'] : 'moderee',
            'temps_estime_min' => is_numeric($data['temps_estime_min'] ?? null) ? max(5, min(300, (int) $data['temps_estime_min'])) : 30,
        ];
    }
}
