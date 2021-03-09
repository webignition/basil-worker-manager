<?php

declare(strict_types=1);

namespace App\Request;

use Symfony\Component\HttpFoundation\Request;

class WorkerCreateRequest extends AbstractEncapsulatingRequest
{
    public const KEY_LABEL = 'label';

    private string $label = '';

    public function processRequest(Request $request): void
    {
        $requestData = $request->request;

        $this->label = (string) $requestData->get(self::KEY_LABEL);
    }

    public function getLabel(): string
    {
        return $this->label;
    }
}
