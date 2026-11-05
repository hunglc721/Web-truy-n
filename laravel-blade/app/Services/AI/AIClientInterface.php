<?php

namespace App\Services\AI;

interface AIClientInterface
{
    /**
     * @param  array{temperature?: float, max_tokens?: int}  $options
     * @return array{success: bool, content: ?string, error: ?string}
     */
    public function complete(string $instruction, string $message, array $options = []): array;
}
