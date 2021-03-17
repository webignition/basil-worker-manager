<?php

namespace App\Entity;

use App\Model\ProviderInterface;
use App\Model\RemoteMachineInterface;
use App\Model\Worker\State;
use App\Repository\WorkerRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;

/**
 * @ORM\Entity(repositoryClass=WorkerRepository::class)
 */
class Worker implements \Stringable, \JsonSerializable
{
    private const NAME = 'worker-%s';

    /**
     * @ORM\Id
     * @ORM\Column(type="ulid", unique=true)
     * @ORM\GeneratedValue(strategy="CUSTOM")
     * @ORM\CustomIdGenerator(class=UlidGenerator::class)
     */
    private ?string $id = null;

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
     * @var State::VALUE_*
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
        $worker->state = STATE::VALUE_CREATE_RECEIVED;
        $worker->provider = $provider;
        $worker->ip_addresses = [];

        return $worker;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getRemoteId(): ?int
    {
        return $this->remote_id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return ProviderInterface::NAME_*
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getName(): string
    {
        return sprintf(self::NAME, $this->getId());
    }

    public function updateFromRemoteMachine(RemoteMachineInterface $remoteMachine): self
    {
        $this->remote_id = $remoteMachine->getId();
        $this->ip_addresses = $remoteMachine->getIpAddresses();

        $remoteMachineState = $remoteMachine->getState();
        if (null !== $remoteMachineState) {
            $this->state = $remoteMachineState;
        }

        return $this;
    }

    /**
     * @return State::VALUE_*
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param  State::VALUE_* $state
     */
    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'label' => $this->label,
            'state' => $this->state,
            'ip_addresses' => $this->ip_addresses,
        ];
    }
}
