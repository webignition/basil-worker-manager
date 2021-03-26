<?php

declare(strict_types=1);

namespace App\Message;

abstract class AbstractRemoteMachineRequest extends AbstractMachineRequest implements RemoteMachineRequestInterface
{
    use RetryableRequestTrait;

    public function getType(): string
    {
        return $this->getAction();
    }
}
