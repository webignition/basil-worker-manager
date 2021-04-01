<?php

namespace App\Model;

interface MachineInterface extends \JsonSerializable
{
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
    ];

    public function getId(): string;
    public function getRemoteId(): ?int;

    /**
     * @return ProviderInterface::NAME_*
     */
    public function getProvider(): string;
    public function getName(): string;

    /**
     * @return MachineInterface::STATE_*
     */
    public function getState(): string;

    /**
     * @param MachineInterface::STATE_* $state
     */
    public function setState(string $state): MachineInterface;

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array;

    public function updateFromRemoteMachine(RemoteMachineInterface $remoteMachine): MachineInterface;
}
