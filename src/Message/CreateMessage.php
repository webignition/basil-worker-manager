<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\WorkerActionRequest;

class CreateMessage
{
    public function __construct(
        private WorkerActionRequest $request,
    ) {
    }

    public function getRequest(): WorkerActionRequest
    {
        return $this->request;
    }
}
