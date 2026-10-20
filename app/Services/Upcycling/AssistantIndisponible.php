<?php

namespace App\Services\Upcycling;

class AssistantIndisponible extends \RuntimeException
{
    public function __construct(string $message, public readonly string $raison = 'unavailable', public readonly ?int $retryAfter = null)
    {
        parent::__construct($message);
    }
}
