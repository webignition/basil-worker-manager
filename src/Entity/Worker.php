<?php

namespace App\Entity;

use App\Model\ProviderInterface;
use App\Model\RemoteMachineInterface;
use App\Repository\WorkerRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=WorkerRepository::class)
 */
class Worker
{
    private const NAME = 'worker-%d';

    public const STATE_CREATE_RECEIVED = 'create/received';
    public const STATE_CREATE_PROCESSING = 'create/processing';
    public const STATE_CREATE_REQUESTED = 'create/requested';
    public const STATE_DELETE_RECEIVED = 'delete/received';
    public const STATE_DELETE_PROCESSING = 'delete/processing';
    public const STATE_UP_STARTED = 'up/started';
    public const STATE_UP_ACTIVE = 'up/active';
    public const STATE_UP_STOPPED = 'up/stopped';
    public const STATE_DELETED = 'deleted';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $remote_id;

    /**
     * @ORM\Column(type="string", length=32, nullable=false, unique=true)
     */
    private string $label;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @var self::STATE_*
     */
    private string $state;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @var ProviderInterface::NAME_*
     */
    private string $provider;

    /**
     * @ORM\Column(type="simple_array", nullable=true)
     *
     * @var string[]
     */
    private array $ip_addresses = [];

    /**
     * @param ProviderInterface::NAME_* $provider
     */
    public static function create(string $label, string $provider): self
    {
        $worker = new Worker();
        $worker->remote_id = null;
        $worker->label = $label;
        $worker->state = self::STATE_CREATE_RECEIVED;
        $worker->provider = $provider;
        $worker->ip_addresses = [];

        return $worker;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getName(): string
    {
        return sprintf(self::NAME, (int) $this->getId());
    }

    public function updateFromRemoteMachine(RemoteMachineInterface $remoteMachine): self
    {
        $this->remote_id = $remoteMachine->getId();
        $this->ip_addresses = $remoteMachine->getIpAddresses();

        return $this;
    }
}
