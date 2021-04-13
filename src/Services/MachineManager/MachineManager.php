<?php

namespace App\Services\MachineManager;

use App\Exception\MachineProvider\MachineNotFoundException;
use App\Exception\UnsupportedProviderException;
use App\Services\ExceptionFactory\MachineProvider\ExceptionFactory;
use App\Services\MachineNameFactory;
use App\Services\MachineUpdater;
use webignition\BasilWorkerManager\PersistenceBundle\Entity\Machine;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Factory\MachineProviderFactory;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Store\MachineProviderStore;
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
        private MachineUpdater $machineUpdater,
        private MachineProviderFactory $machineProviderFactory,
        private MachineProviderStore $machineProviderStore,
    ) {
        $this->machineManagers = array_filter($machineManagers, function ($item) {
            return $item instanceof MachineManagerInterface;
        });
    }

    public function find(string $machineId): void
    {
        $machineName = $this->machineNameFactory->create($machineId);

        $remoteMachine = null;
        foreach ($this->machineManagers as $machineManager) {
            if (null === $remoteMachine) {
                $remoteMachine = $machineManager->get($machineName);

                if ($remoteMachine instanceof RemoteMachineInterface) {
                    $machine = $this->machineStore->find($machineId);
                    if (null === $machine) {
                        $machine = new Machine($machineId);
                    }

                    $this->machineUpdater->updateFromRemoteMachine($machine, $remoteMachine);

                    if (null === $this->machineProviderStore->find($machineId)) {
                        $this->machineProviderFactory->create($machineId, $machineManager->getType());
                    }
                }
            }
        }
    }

    /**
     * @throws ExceptionInterface
     * @throws UnsupportedProviderException
     */
    public function create(MachineProviderInterface $machineProvider): RemoteMachineInterface
    {
        $machineName = $this->machineNameFactory->create($machineProvider->getId());

        try {
            return $this->findProvider($machineProvider)->create($machineName);
        } catch (UnsupportedProviderException $unsupportedProviderException) {
            throw $unsupportedProviderException;
        } catch (\Exception $exception) {
            throw $this->exceptionFactory->create($machineProvider->getId(), Action::ACTION_CREATE, $exception);
        }
    }

    /**
     * @throws ExceptionInterface
     * @throws UnsupportedProviderException
     * @throws MachineNotFoundException
     */
    public function get(MachineProviderInterface $machineProvider): RemoteMachineInterface
    {
        $machineName = $this->machineNameFactory->create($machineProvider->getId());

        try {
            $machine = $this->findProvider($machineProvider)->get($machineName);
            if ($machine instanceof RemoteMachineInterface) {
                return $machine;
            }
        } catch (UnsupportedProviderException $unsupportedProviderException) {
            throw $unsupportedProviderException;
        } catch (\Exception $exception) {
            throw $this->exceptionFactory->create($machineProvider->getId(), Action::ACTION_GET, $exception);
        }

        throw new MachineNotFoundException($machineProvider->getId(), $machineProvider->getName());
    }

    /**
     * @throws ExceptionInterface
     * @throws UnsupportedProviderException
     */
    public function delete(MachineProviderInterface $machineProvider): void
    {
        $machineName = $this->machineNameFactory->create($machineProvider->getId());

        try {
            $this->findProvider($machineProvider)->remove($machineName);
        } catch (UnsupportedProviderException $unsupportedProviderException) {
            throw $unsupportedProviderException;
        } catch (\Exception $exception) {
            throw $this->exceptionFactory->create($machineProvider->getId(), Action::ACTION_DELETE, $exception);
        }
    }

    /**
     * @throws ExceptionInterface
     * @throws UnsupportedProviderException
     */
    public function exists(MachineProviderInterface $machineProvider): bool
    {
        $machineName = $this->machineNameFactory->create($machineProvider->getId());

        try {
            return $this->findProvider($machineProvider)->exists($machineName);
        } catch (UnsupportedProviderException $unsupportedProviderException) {
            throw $unsupportedProviderException;
        } catch (\Exception $exception) {
            throw $this->exceptionFactory->create($machineProvider->getId(), Action::ACTION_EXISTS, $exception);
        }
    }

    /**
     * @throws UnsupportedProviderException
     */
    private function findProvider(MachineProviderInterface $machineProvider): MachineManagerInterface
    {
        $providerName = $machineProvider->getName();

        foreach ($this->machineManagers as $machineManager) {
            if ($machineManager->getType() === $providerName) {
                return $machineManager;
            }
        }

        throw new UnsupportedProviderException($machineProvider->getName());
    }
}
