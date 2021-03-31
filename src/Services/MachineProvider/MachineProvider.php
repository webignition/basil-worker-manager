<?php

namespace App\Services\MachineProvider;

use App\Exception\MachineProvider\ExceptionInterface;
use App\Exception\UnsupportedProviderException;
use App\Model\MachineInterface;
use App\Model\RemoteMachineInterface;
use App\Model\RemoteRequestActionInterface;
use App\Model\RemoteRequestActionInterface as Action;
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
     */
    public function get(MachineInterface $machine): RemoteMachineInterface
    {
        try {
            return $this->findProvider($machine)->get($machine);
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
