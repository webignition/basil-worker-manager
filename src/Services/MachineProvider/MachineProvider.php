<?php

namespace App\Services\MachineProvider;

use App\Exception\MachineProvider\RemoteMachineNotFoundExceptionInterface;
use App\Exception\UnsupportedProviderException;
use App\Services\ExceptionFactory\MachineProvider\ExceptionFactory;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;
use webignition\BasilWorkerManagerInterfaces\RemoteMachineInterface;
use webignition\BasilWorkerManagerInterfaces\RemoteRequestActionInterface;
use webignition\BasilWorkerManagerInterfaces\RemoteRequestActionInterface as Action;

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
    public function create(MachineInterface $machine): RemoteMachineInterface
    {
        try {
            return $this->findProvider($machine)->create($machine);
        } catch (UnsupportedProviderException $unsupportedProviderException) {
            throw $unsupportedProviderException;
        } catch (\Exception $exception) {
            throw $this->exceptionFactory->create($machine->getId(), Action::ACTION_CREATE, $exception);
        }
    }

    /**
     * @throws ExceptionInterface
     * @throws UnsupportedProviderException
     * @throws RemoteMachineNotFoundExceptionInterface
     */
    public function get(MachineInterface $machine): RemoteMachineInterface
    {
        try {
            return $this->findProvider($machine)->get($machine);
        } catch (UnsupportedProviderException $unsupportedProviderException) {
            throw $unsupportedProviderException;
        } catch (RemoteMachineNotFoundExceptionInterface $remoteMachineNotFoundException) {
            throw $remoteMachineNotFoundException;
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
            $this->findProvider($machine)->remove($machine);
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
            return $provider->exists($machine);
        } catch (\Exception $exception) {
            throw $this->exceptionFactory->create(
                $machine->getId(),
                RemoteRequestActionInterface::ACTION_EXISTS,
                $exception
            );
        }
    }

    /**
     * @throws UnsupportedProviderException
     */
    private function findProvider(MachineInterface $machine): MachineProviderInterface
    {
        foreach ($this->machineProviders as $machineProvider) {
            if ($machineProvider->handles($machine->getProvider())) {
                return $machineProvider;
            }
        }

        throw new UnsupportedProviderException($machine->getProvider());
    }
}
