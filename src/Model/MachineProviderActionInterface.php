<?php

namespace App\Model;

interface MachineProviderActionInterface
{
    public const ACTION_CREATE = 'create';
    public const ACTION_GET = 'get';
    public const ACTION_DELETE = 'delete';
}
