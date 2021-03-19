<?php

namespace App\Services\ExceptionFactory\MachineProvider;

use App\Exception\MachineProvider\CurlException;
use App\Exception\MachineProvider\ExceptionInterface;
use App\Model\MachineProviderActionInterface;
use GuzzleHttp\Exception\ConnectException;

class GuzzleExceptionFactory
{
    public const CURL_CODE_UNKNOWN = -1;
    private const CURL_ERROR_PREFIX = 'cURL error ';

    public function handles(\Throwable $exception): bool
    {
        return $exception instanceof ConnectException;
    }

    /**
     * @param MachineProviderActionInterface::ACTION_* $action
     */
    public function create(string $resourceId, string $action, ConnectException $exception): ExceptionInterface
    {
        return new CurlException($this->findCurlCode($exception), $resourceId, $action, 0, $exception);
    }

    private function findCurlCode(ConnectException $exception): int
    {
        $parts = explode(':', $exception->getMessage());
        $curlErrorPart = $parts[0] ?? '';

        return str_starts_with($curlErrorPart, self::CURL_ERROR_PREFIX)
            ? (int) str_replace(self::CURL_ERROR_PREFIX, '', $parts[0])
            : self::CURL_CODE_UNKNOWN;
    }
}
