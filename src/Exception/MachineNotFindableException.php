<?php

namespace App\Exception;

class MachineNotFindableException extends \Exception
{
    /**
     * @var \Throwable[]
     */
    private array $exceptionStack;

    /**
     * @param \Throwable[] $exceptionStack
     */
    public function __construct(
        private string $id,
        array $exceptionStack = [],
    ) {
        parent::__construct();

        $this->exceptionStack = array_filter($exceptionStack, function ($item) {
            return $item instanceof \Throwable;
        });
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return \Throwable[]
     */
    public function getExceptionStack(): array
    {
        return $this->exceptionStack;
    }
}
