<?php

namespace App\Services\ServiceStatusInspector;

use App\Model\ServiceStatus\ComponentStatus;
use App\Model\ServiceStatus\ComponentStatusInterface;

class ComponentInspector implements ComponentInspectorInterface
{
    /**
     * @var callable
     */
    private $inspector;

    /**
     * @var ExceptionHandlerInterface[]
     */
    private array $exceptionHandlers;

    /**
     * @param ExceptionHandlerInterface[] $exceptionHandlers
     */
    public function __construct(
        private string $name,
        callable $handler,
        array $exceptionHandlers
    ) {
        $this->inspector = $handler;

        $this->exceptionHandlers = array_filter($exceptionHandlers, function ($value) {
            return $value instanceof ExceptionHandlerInterface;
        });
    }

    public function getStatus(): ComponentStatusInterface
    {
        $status = new ComponentStatus($this->name);

        try {
            ($this->inspector)();
        } catch (\Throwable $exception) {
            $status = $status->withUnavailable();

            foreach ($this->exceptionHandlers as $exceptionHandler) {
                if ($exceptionHandler->handles($exception)) {
                    $status = $status->withUnavailableReason($exceptionHandler->handle($exception));
                }
            }
        }

        return $status;
    }
}
