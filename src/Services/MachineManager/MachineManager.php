<?php

namespace App\Services\MachineManager;

use App\Exception\UnsupportedProviderException;
use App\Services\ExceptionFactory\MachineProvider\ExceptionFactory;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\MachineProviderInterface;
use webignition\BasilWorkerManagerInterfaces\RemoteMachineInterface;
use webignition\BasilWorkerManagerInterfaces\RemoteRequestActionInterface as Action;

class MachineManager
{
    private const MACHINE_NAME = 'worker-%s';

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
    ) {
        $this->machineManagers = array_filter($machineManagers, function ($item) {
            return $item instanceof MachineManagerInterface;
        });
    }

    /**
     * @throws ExceptionInterface
     * @throws UnsupportedProviderException
     */
    public function create(MachineProviderInterface $machineProvider): RemoteMachineInterface
    {
        try {
            return $this->findProvider($machineProvider)->create(
                sprintf(self::MACHINE_NAME, $machineProvider->getId())
            );
        } catch (UnsupportedProviderException $unsupportedProviderException) {
            throw $unsupportedProviderException;
        } catch (\Exception $exception) {
            throw $this->exceptionFactory->create($machineProvider->getId(), Action::ACTION_CREATE, $exception);
        }
    }

    /**
     * @throws ExceptionInterface
     * @throws UnsupportedProviderException
     */
    public function get(MachineProviderInterface $machineProvider): RemoteMachineInterface
    {
        try {
            return $this->findProvider($machineProvider)->get((int) $machineProvider->getRemoteId());
        } catch (UnsupportedProviderException $unsupportedProviderException) {
            throw $unsupportedProviderException;
        } catch (\Exception $exception) {
            throw $this->exceptionFactory->create($machineProvider->getId(), Action::ACTION_GET, $exception);
        }
    }

    /**
     * @throws ExceptionInterface
     * @throws UnsupportedProviderException
     */
    public function delete(MachineProviderInterface $machineProvider): void
    {
        try {
            $this->findProvider($machineProvider)->remove((int) $machineProvider->getRemoteId());
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
        $provider = $this->findProvider($machineProvider);

        try {
            return $provider->exists((int) $machineProvider->getRemoteId());
        } catch (\Exception $exception) {
            throw $this->exceptionFactory->create(
                $machineProvider->getId(),
                Action::ACTION_EXISTS,
                $exception
            );
        }
    }

    /**
     * @throws UnsupportedProviderException
     */
    private function findProvider(MachineProviderInterface $machineProvider): MachineManagerInterface
    {
        foreach ($this->machineManagers as $machineManager) {
            if ($machineManager->handles($machineProvider->getName())) {
                return $machineManager;
            }
        }

        throw new UnsupportedProviderException($machineProvider->getName());
    }
}
