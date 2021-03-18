<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\ApiRequest\WorkerRequest;

class CreateMessage implements WorkerRequestMessageInterface
{
    public function __construct(
        private WorkerRequest $request,
    ) {
    }

    public function getRequest(): WorkerRequest
    {
        return $this->request;
    }
}
