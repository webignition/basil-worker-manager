<?php

namespace App\Exception\MachineProvider;

use webignition\BasilWorkerManagerInterfaces\ProviderInterface;

class MachineNotFoundException extends \Exception implements MachineNotFoundExceptionInterface
{
    /**
     * @param ProviderInterface::NAME_* $providerName|null
     */
    public function __construct(
        private string $id,
        private ?string $providerName,
    ) {
        parent::__construct();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getProviderName(): ?string
    {
        return $this->providerName;
    }
}
