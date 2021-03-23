<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\ApiRequest\WorkerRequestInterface;

abstract class AbstractMessage implements WorkerRequestMessageInterface
{
    public function __construct(
        private WorkerRequestInterface $request
    ) {
    }

    public function getRequest(): WorkerRequestInterface
    {
        return $this->request;
    }
}
