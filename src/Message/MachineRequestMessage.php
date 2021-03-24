<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\MachineRequestInterface;

class MachineRequestMessage
{
    public function __construct(
        private MachineRequestInterface $request
    ) {
    }

    public function getRequest(): MachineRequestInterface
    {
        return $this->request;
    }
}
