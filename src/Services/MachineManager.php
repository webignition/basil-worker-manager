<?php

namespace App\Services;

use App\Exception\MachineProvider\ProviderMachineNotFoundException;
use App\Exception\UnsupportedProviderException;
use App\Services\ExceptionFactory\MachineProvider\ExceptionFactory;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Store\MachineStore;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\MachineActionInterface as Action;
use webignition\BasilWorkerManagerInterfaces\MachineProviderInterface;
use webignition\BasilWorkerManagerInterfaces\RemoteMachineInterface;

class MachineManager extends AbstractMachineManager
{
    /**
     * @param ProviderMachineManagerInterface[] $machineManagers
     */
    public function __construct(
        array $machineManagers,
        MachineNameFactory $machineNameFactory,
        private ExceptionFactory $exceptionFactory,
        private MachineStore $machineStore,
    ) {
        parent::__construct($machineManagers, $machineNameFactory);
    }

    /**
     * @throws ExceptionInterface
     * @throws UnsupportedProviderException
     */
    public function create(MachineProviderInterface $machineProvider): RemoteMachineInterface
    {
        $machineId = $machineProvider->getId();
        $machineName = $this->createMachineName($machineId);

        $provider = $this->findProvider($machineProvider);
        if (null === $provider) {
            throw new UnsupportedProviderException($machineProvider->getName());
        }

        try {
            return $provider->create($machineId, $machineName);
        } catch (ExceptionInterface $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            throw $this->exceptionFactory->create($machineId, Action::ACTION_CREATE, $exception);
        }
    }

    /**
     * @throws ExceptionInterface
     * @throws UnsupportedProviderException
     * @throws ProviderMachineNotFoundException
     */
    public function get(MachineProviderInterface $machineProvider): RemoteMachineInterface
    {
        $machineId = $machineProvider->getId();
        $machineName = $this->createMachineName($machineId);

        $provider = $this->findProvider($machineProvider);
        if (null === $provider) {
            throw new UnsupportedProviderException($machineProvider->getName());
        }

        try {
            $machine = $provider->get($machineId, $machineName);
            if ($machine instanceof RemoteMachineInterface) {
                return $machine;
            }
        } catch (ExceptionInterface $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            throw $this->exceptionFactory->create($machineId, Action::ACTION_GET, $exception);
        }

        throw new ProviderMachineNotFoundException($machineProvider->getId(), $machineProvider->getName());
    }

    /**
     * @throws ExceptionInterface
     * @throws UnsupportedProviderException
     */
    public function delete(MachineProviderInterface $machineProvider): void
    {
        $machineId = $machineProvider->getId();
        $machineName = $this->createMachineName($machineId);

        $provider = $this->findProvider($machineProvider);
        if (null === $provider) {
            throw new UnsupportedProviderException($machineProvider->getName());
        }

        try {
            $provider->remove($machineId, $machineName);
        } catch (ExceptionInterface $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            throw $this->exceptionFactory->create($machineId, Action::ACTION_DELETE, $exception);
        }
    }

    /**
     * @throws ExceptionInterface
     * @throws UnsupportedProviderException
     */
    public function exists(MachineProviderInterface $machineProvider): bool
    {
        $machineId = $machineProvider->getId();
        $machineName = $this->createMachineName($machineId);

        $provider = $this->findProvider($machineProvider);
        if (null === $provider) {
            throw new UnsupportedProviderException($machineProvider->getName());
        }

        try {
            return $provider->exists($machineId, $machineName);
        } catch (ExceptionInterface $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            throw $this->exceptionFactory->create($machineId, Action::ACTION_GET, $exception);
        }
    }
}
