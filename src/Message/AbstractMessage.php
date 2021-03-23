<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\ApiRequest\WorkerRequest;

abstract class AbstractMessage implements WorkerRequestMessageInterface
{
    public function __construct(
        private WorkerRequest $request
    ) {
    }

    public function getRequest(): WorkerRequest
    {
        return $this->request;
    }
}
