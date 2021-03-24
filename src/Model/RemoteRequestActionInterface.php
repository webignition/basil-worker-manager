<?php

namespace App\Model;

interface RemoteRequestActionInterface
{
    public const ACTION_CREATE = 'create';
    public const ACTION_GET = 'get';
    public const ACTION_DELETE = 'delete';

    public const ALL = [
        self::ACTION_CREATE,
        self::ACTION_GET,
        self::ACTION_DELETE,
    ];
}
