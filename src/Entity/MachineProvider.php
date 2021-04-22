<?php

namespace App\Entity;

use App\Model\MachineProviderInterface;
use App\Model\ProviderInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class MachineProvider implements MachineProviderInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=MachineIdInterface::LENGTH)
     */
    private string $id;

    /**
     * @ORM\Column(type="string", length=255)
     *
     * @var ProviderInterface::NAME_*
     */
    private string $provider;

    /**
     * @param ProviderInterface::NAME_* $provider
     */
    public function __construct(string $id, string $provider)
    {
        $this->id = $id;
        $this->provider = $provider;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return ProviderInterface::NAME_*
     */
    public function getName(): string
    {
        return $this->provider;
    }

    public function merge(MachineProviderInterface $machineProvider): self
    {
        $this->provider = $machineProvider->getName();

        return $this;
    }
}
