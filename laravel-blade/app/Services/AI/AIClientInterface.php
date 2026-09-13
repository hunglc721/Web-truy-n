<?php

namespace App\Services\AI;

interface AIClientInterface
{
    /** @return array{success: bool, content: ?string, error: ?string} */
    public function complete(string $instruction, string $message): array;
}
