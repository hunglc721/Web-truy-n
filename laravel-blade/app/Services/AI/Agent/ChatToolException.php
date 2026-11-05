<?php

namespace App\Services\AI\Agent;

use RuntimeException;

/** Marker for tool failures that are safe to report to the model. */
final class ChatToolException extends RuntimeException
{
    public function __construct(public readonly string $reason)
    {
        parent::__construct($reason);
    }
}
