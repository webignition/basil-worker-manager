<?php

namespace App\Model;

interface MachineInterface extends \JsonSerializable
{
    public const STATE_UNKNOWN = 'unknown';
    public const STATE_FIND_RECEIVED = 'find/received';
    public const STATE_FIND_FINDING = 'find/finding';
    public const STATE_FIND_NOT_FOUND = 'find/not-found';
    public const STATE_FIND_NOT_FINDABLE = 'find/not-findable';
    public const STATE_CREATE_RECEIVED = 'create/received';
    public const STATE_CREATE_REQUESTED = 'create/requested';
    public const STATE_CREATE_FAILED = 'create/failed';
    public const STATE_UP_STARTED = 'up/started';
    public const STATE_UP_ACTIVE = 'up/active';
    public const STATE_DELETE_RECEIVED = 'delete/received';
    public const STATE_DELETE_REQUESTED = 'delete/requested';
    public const STATE_DELETE_FAILED = 'delete/failed';
    public const STATE_DELETE_DELETED = 'delete/deleted';

    public const PRE_ACTIVE_STATES = [
        self::STATE_CREATE_RECEIVED,
        self::STATE_CREATE_REQUESTED,
        self::STATE_UP_STARTED,
    ];

    public const END_STATES = [
        self::STATE_CREATE_FAILED,
        self::STATE_DELETE_FAILED,
        self::STATE_DELETE_DELETED,
        self::STATE_FIND_NOT_FINDABLE,
        self::STATE_FIND_NOT_FOUND,
    ];

    public const RESETTABLE_STATES = [
        self::STATE_FIND_NOT_FOUND,
        self::STATE_CREATE_FAILED,
    ];

    public function getId(): string;
    public function getName(): string;

    /**
     * @return MachineInterface::STATE_*
     */
    public function getState(): string;

    /**
     * @param MachineInterface::STATE_* $state
     */
    public function setState(string $state): void;

    /**
     * @return string[]
     */
    public function getIpAddresses(): array;

    /**
     * @param string[] $ipAddresses
     */
    public function setIpAddresses(array $ipAddresses): void;

    public function reset(): void;

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array;
}
