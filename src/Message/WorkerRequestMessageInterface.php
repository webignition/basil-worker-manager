<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\ApiRequest\WorkerRequestInterface;

interface WorkerRequestMessageInterface
{
    public function getRequest(): WorkerRequestInterface;
}
