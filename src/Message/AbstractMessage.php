<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\ApiRequest\WorkerRequestInterface;
use App\Model\MachineProviderActionInterface;

abstract class AbstractMessage implements WorkerRequestMessageInterface
{
    /**
     * @param MachineProviderActionInterface::ACTION_* $type
     */
    public function __construct(
        private string $type,
        private WorkerRequestInterface $request
    ) {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getRequest(): WorkerRequestInterface
    {
        return $this->request;
    }
}
