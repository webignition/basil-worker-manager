<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\ApiRequest\WorkerRequestInterface;
use App\Model\MachineProviderActionInterface;

interface WorkerRequestMessageInterface
{
    /**
     * @return MachineProviderActionInterface::ACTION_*
     */
    public function getType(): string;
    public function getRequest(): WorkerRequestInterface;
}
