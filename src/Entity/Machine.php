<?php

namespace App\Entity;

use App\Repository\MachineRepository;
use Doctrine\ORM\Mapping as ORM;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;
use webignition\BasilWorkerManagerInterfaces\ProviderInterface;

/**
 * @ORM\Entity(repositoryClass=MachineRepository::class)
 */
class Machine implements MachineInterface
{
    private const NAME = 'worker-%s';

    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=64)
     */
    private string $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $remote_id;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @var MachineInterface::STATE_*
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
     * @param MachineInterface::STATE_* $state
     * @param string[] $ipAddresses
     */
    public function __construct(
        string $id,
        string $provider,
        ?int $remoteId = null,
        string $state = MachineInterface::STATE_CREATE_RECEIVED,
        array $ipAddresses = [],
    ) {
        $this->id = $id;
        $this->provider = $provider;
        $this->remote_id = $remoteId;
        $this->state = $state;
        $this->ip_addresses = $ipAddresses;
    }

    /**
     * @param ProviderInterface::NAME_* $provider
     */
    public static function create(string $id, string $provider): self
    {
        return new Machine($id, $provider);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getRemoteId(): ?int
    {
        return $this->remote_id;
    }

    public function setRemoteId(int $remoteId): MachineInterface
    {
        $this->remote_id = $remoteId;

        return $this;
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
    public function setState(string $state): self
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getIpAddresses(): array
    {
        return $this->ip_addresses;
    }

    public function setIpAddresses(array $ipAddresses): MachineInterface
    {
        $this->ip_addresses = $ipAddresses;

        return $this;
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
}
