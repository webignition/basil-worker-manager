<?php

namespace App\Services\MachineManager;

use App\Exception\UnsupportedProviderException;
use App\Services\ExceptionFactory\MachineProvider\ExceptionFactory;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;
use webignition\BasilWorkerManagerInterfaces\RemoteMachineInterface;
use webignition\BasilWorkerManagerInterfaces\RemoteRequestActionInterface as Action;

class MachineManager
{
    private const MACHINE_NAME = 'worker-%s';

    /**
     * @var MachineManagerInterface[]
     */
    private array $machineProviders;

    /**
     * @param MachineManagerInterface[] $machineProviders
     */
    public function __construct(
        array $machineProviders,
        private ExceptionFactory $exceptionFactory,
    ) {
        $this->machineProviders = array_filter($machineProviders, function ($item) {
            return $item instanceof MachineManagerInterface;
        });
    }

    /**
     * @throws ExceptionInterface
     * @throws UnsupportedProviderException
     */
    public function create(MachineInterface $machine): RemoteMachineInterface
    {
        try {
            return $this->findProvider($machine)->create(sprintf(self::MACHINE_NAME, $machine->getId()));
        } catch (UnsupportedProviderException $unsupportedProviderException) {
            throw $unsupportedProviderException;
        } catch (\Exception $exception) {
            throw $this->exceptionFactory->create($machine->getId(), Action::ACTION_CREATE, $exception);
        }
    }

    /**
     * @throws ExceptionInterface
     * @throws UnsupportedProviderException
     */
    public function get(MachineInterface $machine): RemoteMachineInterface
    {
        try {
            return $this->findProvider($machine)->get((int) $machine->getRemoteId());
        } catch (UnsupportedProviderException $unsupportedProviderException) {
            throw $unsupportedProviderException;
        } catch (\Exception $exception) {
            throw $this->exceptionFactory->create($machine->getId(), Action::ACTION_GET, $exception);
        }
    }

    /**
     * @throws ExceptionInterface
     * @throws UnsupportedProviderException
     */
    public function delete(MachineInterface $machine): void
    {
        try {
            $this->findProvider($machine)->remove((int) $machine->getRemoteId());
        } catch (UnsupportedProviderException $unsupportedProviderException) {
            throw $unsupportedProviderException;
        } catch (\Exception $exception) {
            throw $this->exceptionFactory->create($machine->getId(), Action::ACTION_DELETE, $exception);
        }
    }

    /**
     * @throws ExceptionInterface
     * @throws UnsupportedProviderException
     */
    public function exists(MachineInterface $machine): bool
    {
        $provider = $this->findProvider($machine);

        try {
            return $provider->exists((int) $machine->getRemoteId());
        } catch (\Exception $exception) {
            throw $this->exceptionFactory->create(
                $machine->getId(),
                Action::ACTION_EXISTS,
                $exception
            );
        }
    }

    /**
     * @throws UnsupportedProviderException
     */
    private function findProvider(MachineInterface $machine): MachineManagerInterface
    {
        foreach ($this->machineProviders as $machineProvider) {
            if ($machineProvider->handles($machine->getProvider())) {
                return $machineProvider;
            }
        }

        throw new UnsupportedProviderException($machine->getProvider());
    }
}
