<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

interface NonDispatchableMessageExceptionInterface extends \Throwable
{
    public function getMessageObject(): object;
}
