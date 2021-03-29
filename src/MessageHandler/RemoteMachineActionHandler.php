<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Machine;
use App\Model\RemoteRequestOutcomeInterface;

class RemoteMachineActionHandler implements RemoteMachineActionHandlerInterface
{
    /**
     * @var callable
     */
    private $action;

    /**
     * @var ?callable
     */
    private $outcomeHandler = null;

    /**
     * @var ?callable
     */
    private $successHandler = null;

    /**
     * @var ?callable
     */
    private $failureHandler = null;

    /**
     * @var ?callable
     */
    private $beforeRequestHandler = null;

    public function __construct(callable $action)
    {
        $this->action = $action;
    }

    public function withOutcomeHandler(callable $outcomeHandler): self
    {
        $this->outcomeHandler = $outcomeHandler;

        return $this;
    }

    public function withSuccessHandler(callable $successHandler): self
    {
        $this->successHandler = $successHandler;

        return $this;
    }

    public function withFailureHandler(callable $failureHandler): self
    {
        $this->failureHandler = $failureHandler;

        return $this;
    }

    public function withBeforeRequestHandler(callable $beforeRequestHandler): self
    {
        $this->beforeRequestHandler = $beforeRequestHandler;

        return $this;
    }

    public function performAction(Machine $machine): RemoteRequestOutcomeInterface
    {
        return ($this->action)($machine);
    }

    public function onOutcome(RemoteRequestOutcomeInterface $outcome): RemoteRequestOutcomeInterface
    {
        if (is_callable($this->outcomeHandler)) {
            return ($this->outcomeHandler)($outcome);
        }

        return $outcome;
    }

    public function onSuccess(Machine $machine, RemoteRequestOutcomeInterface $outcome): void
    {
        if (is_callable($this->successHandler)) {
            ($this->successHandler)($machine, $outcome);
        }
    }

    public function onFailure(Machine $machine, \Throwable $exception): void
    {
        if (is_callable($this->failureHandler)) {
            ($this->failureHandler)($machine, $exception);
        }
    }

    public function onBeforeRequest(Machine $machine): void
    {
        if (is_callable($this->beforeRequestHandler)) {
            ($this->beforeRequestHandler)($machine);
        }
    }
}
