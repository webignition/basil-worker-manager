<?php

namespace App\Exception\MachineProvider;

interface ExceptionInterface
{
    public const ACTION_CREATE = 'create';
    public const ACTION_GET = 'get';
    public const ACTION_DELETE = 'delete';

    public function getRemoteException(): \Throwable;

    /**
     * @return self::ACTION_*
     */
    public function getAction(): string;
}
