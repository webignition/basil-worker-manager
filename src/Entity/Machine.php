<?php

namespace App\Entity;

use App\Model\MachineInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Machine implements MachineInterface
{
    private const NAME = 'worker-%s';

    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=MachineIdInterface::LENGTH)
     */
    private string $id;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @var MachineInterface::STATE_*
     */
    private string $state;

    /**
     * @ORM\Column(type="simple_array", nullable=true)
     *
     * @var string[]
     */
    private array $ip_addresses;

    /**
     * @param MachineInterface::STATE_* $state
     * @param string[] $ipAddresses
     */
    public function __construct(
        string $id,
        string $state = MachineInterface::STATE_CREATE_RECEIVED,
        array $ipAddresses = [],
    ) {
        $this->id = $id;
        $this->state = $state;
        $this->ip_addresses = $ipAddresses;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return sprintf(self::NAME, $this->id);
    }

    /**
     * @return MachineInterface::STATE_*
     */
    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @param MachineInterface::STATE_* $state
     */
    public function setState(string $state): void
    {
        $this->state = $state;
    }

    /**
     * @return string[]
     */
    public function getIpAddresses(): array
    {
        return $this->ip_addresses;
    }

    public function setIpAddresses(array $ipAddresses): void
    {
        $this->ip_addresses = $ipAddresses;
    }

    public function reset(): void
    {
        $this->state = MachineInterface::STATE_CREATE_RECEIVED;
        $this->ip_addresses = [];
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'state' => $this->state,
            'ip_addresses' => $this->ip_addresses,
        ];
    }

    public function merge(MachineInterface $machine): self
    {
        $this->state = $machine->getState();

        $ipAddresses = $machine->getIpAddresses();
        if ([] !== $ipAddresses) {
            $this->ip_addresses = $machine->getIpAddresses();
        }

        return $this;
    }
}
