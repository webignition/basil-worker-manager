<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\ApiRequest\UpdateWorkerRequest;

class UpdateWorkerMessage
{
    public function __construct(
        private UpdateWorkerRequest $request
    ) {
    }

    public function getRequest(): UpdateWorkerRequest
    {
        return $this->request;
    }
}
