<?php

namespace App\Exception;

class LoggableException extends \Exception
{
    private const TRACE_FILE_KEY = 'file';
    private const TRACE_LINE_KEY = 'line';

    /**
     * @param array<mixed> $context
     */
    public function __construct(
        private \Throwable $exception,
        private array $context = []
    ) {
        parent::__construct((string) $this);
    }

    public function getException(): \Throwable
    {
        return $this->exception;
    }

    /**
     * @return array<mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        $trace = $this->exception->getTrace();
        $data = [
            'code' => $this->exception->getCode(),
            'message' => $this->exception->getMessage(),
            'called' => $this->createCalledSection($trace[0]),
            'occurred' => $this->createLocationSectionForThrowable($this->exception),
            'context' => $this->context,
        ];
        $previous = $this->exception->getPrevious();
        if ($previous instanceof \Throwable) {
            $data += [
                'previous' => array_merge(
                    [
                        'message' => $previous->getMessage(),
                        'exception' => get_class($previous),
                    ],
                    $this->createLocationSectionForThrowable($previous)
                ),
            ];
        }

        return $data;
    }

    public function __toString(): string
    {
        return (string) json_encode($this->jsonSerialize());
    }

    /**
     * @param array<mixed> $trace
     *
     * @return array<mixed>
     */
    private function createCalledSection(array $trace): array
    {
        $data = [];

        $file = $trace[self::TRACE_FILE_KEY] ?? null;
        $line = $trace[self::TRACE_LINE_KEY] ?? null;

        $file = null === $file ? null : (string) $file;
        $line = null === $line ? null : (int) $line;

        if (is_string($file) && is_int($line)) {
            $data = $this->createLocationSection($file, $line);
        }

        return $data;
    }

    /**
     * @return array<string, string|int>
     */
    private function createLocationSectionForThrowable(\Throwable $throwable): array
    {
        return $this->createLocationSection($throwable->getFile(), $throwable->getLine());
    }

    /**
     * @return array<string, string|int>
     */
    private function createLocationSection(string $file, int $line): array
    {
        return [
            'file' => $file,
            'line' => $line,
        ];
    }
}
