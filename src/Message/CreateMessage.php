<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\CreateMachineRequest;

class CreateMessage
{
    public function __construct(
        private CreateMachineRequest $request,
    ) {
    }

    public function getRequest(): CreateMachineRequest
    {
        return $this->request;
    }
}
