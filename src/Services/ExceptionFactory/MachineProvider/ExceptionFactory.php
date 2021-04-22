<?php

namespace App\Services\ExceptionFactory\MachineProvider;

use App\Exception\MachineProvider\UnknownException;
use App\Model\MachineActionInterface;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ExceptionInterface;

class ExceptionFactory
{
    /**
     * @var ExceptionFactoryInterface[]
     */
    private array $factories;

    /**
     * @param ExceptionFactoryInterface[] $factories
     */
    public function __construct(array $factories)
    {
        $this->factories = array_filter($factories, function ($value) {
            return $value instanceof ExceptionFactoryInterface;
        });
    }

    /**
     * @param MachineActionInterface::ACTION_* $action
     */
    public function create(string $resourceId, string $action, \Throwable $exception): ExceptionInterface
    {
        foreach ($this->factories as $factory) {
            if ($factory->handles($exception)) {
                $newException = $factory->create($resourceId, $action, $exception);

                if ($newException instanceof ExceptionInterface) {
                    return $newException;
                }
            }
        }

        return new UnknownException($resourceId, $action, $exception);
    }
}
