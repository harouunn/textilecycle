<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Le service d'IA ne peut pas répondre (pas de clé, délai dépassé, quota, erreur serveur, JSON illisible).
 * Toujours interceptée par l'appelant, qui bascule sur une version sans IA.
 */
class AiUnavailableException extends RuntimeException
{
    public function __construct(string $message, public readonly ?int $status = null, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public static function nonConfigure(): self
    {
        return new self('Clé d\'API Gemini absente (GEMINI_API_KEY).');
    }
}
