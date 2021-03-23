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
    public function create(Machine $worker): Machine
    {
        return $this->handle(
            $worker,
            MachineProviderActionInterface::ACTION_CREATE,
            function (MachineProviderInterface $provider, Machine $worker) {
                return $provider->create($worker);
            }
        );
    }

    /**
     * @throws ExceptionInterface
     * @throws UnsupportedProviderException
     */
    public function update(Machine $worker): Machine
    {
        return $this->handle(
            $worker,
            MachineProviderActionInterface::ACTION_GET,
            function (MachineProviderInterface $provider, Machine $worker) {
                return $provider->hydrate($worker);
            }
        );
    }

    /**
     * @throws ExceptionInterface
     * @throws UnsupportedProviderException
     */
    public function delete(Machine $worker): Machine
    {
        return $this->handle(
            $worker,
            MachineProviderActionInterface::ACTION_DELETE,
            function (MachineProviderInterface $provider, Machine $worker) {
                return $provider->remove($worker);
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
        Machine $worker,
        string $action,
        callable $callable
    ): Machine {
        $provider = $this->findProvider($worker);

        try {
            return $callable($provider, $worker);
        } catch (\Exception $exception) {
            throw $this->exceptionFactory->create((string) $worker, $action, $exception);
        }
    }

    /**
     * @throws UnsupportedProviderException
     */
    private function findProvider(Machine $worker): MachineProviderInterface
    {
        foreach ($this->machineProviders as $machineProvider) {
            if ($machineProvider->handles($worker->getProvider())) {
                return $machineProvider;
            }
        }

        throw new UnsupportedProviderException($worker->getProvider());
    }
}
