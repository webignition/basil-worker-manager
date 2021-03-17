<?php

namespace App\Model\Worker;

class State implements \Stringable
{
    public const VALUE_CREATE_RECEIVED = 'create/received';
    public const VALUE_CREATE_REQUESTED = 'create/requested';
    public const VALUE_CREATE_FAILED = 'create/failed';
    public const VALUE_UP_STARTED = 'up/started';
    public const VALUE_UP_ACTIVE = 'up/active';
    public const VALUE_DELETE_RECEIVED = 'delete/received';
    public const VALUE_DELETE_REQUESTED = 'delete/requested';
    public const VALUE_DELETE_FAILED = 'delete/failed';
    public const VALUE_DELETE_DELETED = 'delete/deleted';

    private const ALL = [
        self::VALUE_CREATE_RECEIVED,
        self::VALUE_CREATE_REQUESTED,
        self::VALUE_CREATE_FAILED,
        self::VALUE_UP_STARTED,
        self::VALUE_UP_ACTIVE,
        self::VALUE_DELETE_RECEIVED,
        self::VALUE_DELETE_REQUESTED,
        self::VALUE_DELETE_FAILED,
        self::VALUE_DELETE_DELETED,
    ];

    public const END_STATES = [
        self::VALUE_CREATE_FAILED,
        self::VALUE_DELETE_FAILED,
        self::VALUE_DELETE_DELETED,
    ];

    /**
     * @param self::VALUE_* $value
     */
    public function __construct(
        private string $value
    ) {
    }

    public static function is(string $value): bool
    {
        return in_array($value, self::ALL);
    }

    /**
     * @return self::VALUE_*
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
