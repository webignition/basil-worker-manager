<?php

namespace App\Services\MachineManager;

use App\Exception\MachineProvider\MachineNotFoundException;
use App\Exception\UnsupportedProviderException;
use App\Services\ExceptionFactory\MachineProvider\ExceptionFactory;
use App\Services\MachineNameFactory;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Store\MachineStore;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\MachineActionInterface as Action;
use webignition\BasilWorkerManagerInterfaces\MachineProviderInterface;
use webignition\BasilWorkerManagerInterfaces\RemoteMachineInterface;

class MachineManager
{
    /**
     * @var MachineManagerInterface[]
     */
    private array $machineManagers;

    /**
     * @param MachineManagerInterface[] $machineManagers
     */
    public function __construct(
        array $machineManagers,
        private ExceptionFactory $exceptionFactory,
        private MachineNameFactory $machineNameFactory,
        private MachineStore $machineStore,
    ) {
        $this->machineManagers = array_filter($machineManagers, function ($item) {
            return $item instanceof MachineManagerInterface;
        });
    }

    /**
     * @throws MachineNotFoundException
     */
    public function findRemoteMachine(string $machineId): RemoteMachineInterface
    {
        $machineName = $this->machineNameFactory->create($machineId);

        $remoteMachine = null;
        foreach ($this->machineManagers as $machineManager) {
            if (null === $remoteMachine) {
                try {
                    $remoteMachine = $machineManager->get($machineId, $machineName);
                } catch (ExceptionInterface) {
                }

                if ($remoteMachine instanceof RemoteMachineInterface) {
                    return $remoteMachine;
                }
            }
        }

        throw new MachineNotFoundException($machineId);
    }

    /**
     * @throws ExceptionInterface
     * @throws UnsupportedProviderException
     */
    public function create(MachineProviderInterface $machineProvider): RemoteMachineInterface
    {
        $machineId = $machineProvider->getId();
        $machineName = $this->machineNameFactory->create($machineId);

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
     * @throws MachineNotFoundException
     */
    public function get(MachineProviderInterface $machineProvider): RemoteMachineInterface
    {
        $machineId = $machineProvider->getId();
        $machineName = $this->machineNameFactory->create($machineId);

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

        throw new MachineNotFoundException($machineProvider->getId(), $machineProvider->getName());
    }

    /**
     * @throws ExceptionInterface
     * @throws UnsupportedProviderException
     */
    public function delete(MachineProviderInterface $machineProvider): void
    {
        $machineId = $machineProvider->getId();
        $machineName = $this->machineNameFactory->create($machineId);

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
        $machineName = $this->machineNameFactory->create($machineId);

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

    private function findProvider(MachineProviderInterface $machineProvider): ?MachineManagerInterface
    {
        $providerName = $machineProvider->getName();

        foreach ($this->machineManagers as $machineManager) {
            if ($machineManager->getType() === $providerName) {
                return $machineManager;
            }
        }

        return null;
    }
}
