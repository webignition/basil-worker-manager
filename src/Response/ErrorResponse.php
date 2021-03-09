<?php

declare(strict_types=1);

namespace App\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

class ErrorResponse extends JsonResponse
{
    public function __construct(string $type, string $message, int $code, int $status)
    {
        parent::__construct(
            [
                'type' => $type,
                'message' => $message,
                'code' => $code,
            ],
            $status
        );
    }
}
