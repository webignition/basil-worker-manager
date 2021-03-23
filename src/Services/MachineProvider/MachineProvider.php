<?php

namespace App\Services\MachineProvider;

use App\Entity\Machine;
use App\Exception\MachineProvider\ExceptionInterface;
use App\Exception\UnsupportedProviderException;
use App\Model\MachineProviderActionInterface;
use App\Services\ExceptionFactory\MachineProvider\ExceptionFactory;

class MachineProvider
{
    /**
     * @var MachineProviderInterface[]
     */
    private array $machineProviders;

    /**
     * @param MachineProviderInterface[] $machineProviders
     */
    public function __construct(
        array $machineProviders,
        private ExceptionFactory $exceptionFactory,
    ) {
        $this->machineProviders = array_filter($machineProviders, function ($item) {
            return $item instanceof MachineProviderInterface;
        });
    }

    /**
     * @throws ExceptionInterface
     * @throws UnsupportedProviderException
     */
    public function create(Machine $machine): Machine
    {
        return $this->handle(
            $machine,
            MachineProviderActionInterface::ACTION_CREATE,
            function (MachineProviderInterface $provider, Machine $machine) {
                return $provider->create($machine);
            }
        );
    }

    /**
     * @throws ExceptionInterface
     * @throws UnsupportedProviderException
     */
    public function update(Machine $machine): Machine
    {
        return $this->handle(
            $machine,
            MachineProviderActionInterface::ACTION_GET,
            function (MachineProviderInterface $provider, Machine $machine) {
                return $provider->hydrate($machine);
            }
        );
    }

    /**
     * @throws ExceptionInterface
     * @throws UnsupportedProviderException
     */
    public function delete(Machine $machine): Machine
    {
        return $this->handle(
            $machine,
            MachineProviderActionInterface::ACTION_DELETE,
            function (MachineProviderInterface $provider, Machine $machine) {
                return $provider->remove($machine);
            }
        );
    }

    /**
     * @param MachineProviderActionInterface::ACTION_* $action
     *
     * @throws UnsupportedProviderException
     * @throws ExceptionInterface
     */
    private function handle(
        Machine $machine,
        string $action,
        callable $callable
    ): Machine {
        $provider = $this->findProvider($machine);

        try {
            return $callable($provider, $machine);
        } catch (\Exception $exception) {
            throw $this->exceptionFactory->create((string) $machine, $action, $exception);
        }
    }

    /**
     * @throws UnsupportedProviderException
     */
    private function findProvider(Machine $machine): MachineProviderInterface
    {
        foreach ($this->machineProviders as $machineProvider) {
            if ($machineProvider->handles($machine->getProvider())) {
                return $machineProvider;
            }
        }

        throw new UnsupportedProviderException($machine->getProvider());
    }
}
