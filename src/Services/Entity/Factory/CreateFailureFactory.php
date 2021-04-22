<?php

namespace App\Services\Entity\Factory;

use webignition\BasilWorkerManager\PersistenceBundle\Entity\CreateFailure;
use webignition\BasilWorkerManager\PersistenceBundle\Services\Store\CreateFailureStore;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ApiLimitExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\AuthenticationExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\CurlExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\HttpExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\UnprocessableRequestExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\Exception\UnsupportedProviderExceptionInterface;

class CreateFailureFactory
{
    /**
     * @var array<CreateFailure::CODE_*, CreateFailure::REASON_*>
     */
    public const REASONS = [
        CreateFailure::CODE_UNSUPPORTED_PROVIDER => CreateFailure::REASON_UNSUPPORTED_PROVIDER,
        CreateFailure::CODE_API_LIMIT_EXCEEDED => CreateFailure::REASON_API_LIMIT_EXCEEDED,
        CreateFailure::CODE_API_AUTHENTICATION_FAILURE => CreateFailure::REASON_API_AUTHENTICATION_FAILURE,
        CreateFailure::CODE_CURL_ERROR => CreateFailure::REASON_CURL_ERROR,
        CreateFailure::CODE_HTTP_ERROR => CreateFailure::REASON_HTTP_ERROR,
        CreateFailure::CODE_UNPROCESSABLE_REQUEST => CreateFailure::REASON_UNPROCESSABLE_REQUEST,
    ];

    public function __construct(
        private CreateFailureStore $store,
    ) {
    }

    public function create(
        string $machineId,
        ExceptionInterface | UnsupportedProviderExceptionInterface $exception
    ): CreateFailure {
        $existingEntity = $this->store->find($machineId);
        if ($existingEntity instanceof CreateFailure) {
            return $existingEntity;
        }

        $code = $this->findCode($exception);

        $entity = new CreateFailure($machineId, $code, $this->findReason($code), $this->createContext($exception));
        $this->store->store($entity);

        return $entity;
    }

    /**
     * @param ExceptionInterface|UnsupportedProviderExceptionInterface $exception
     *
     * @return CreateFailure::CODE_*
     */
    private function findCode(ExceptionInterface | UnsupportedProviderExceptionInterface $exception): int
    {
        if ($exception instanceof UnsupportedProviderExceptionInterface) {
            return CreateFailure::CODE_UNSUPPORTED_PROVIDER;
        }

        if ($exception instanceof ApiLimitExceptionInterface) {
            return CreateFailure::CODE_API_LIMIT_EXCEEDED;
        }

        if ($exception instanceof AuthenticationExceptionInterface) {
            return CreateFailure::CODE_API_AUTHENTICATION_FAILURE;
        }

        if ($exception instanceof CurlExceptionInterface) {
            return CreateFailure::CODE_CURL_ERROR;
        }

        if ($exception instanceof HttpExceptionInterface) {
            return CreateFailure::CODE_HTTP_ERROR;
        }

        if ($exception instanceof UnprocessableRequestExceptionInterface) {
            return CreateFailure::CODE_UNPROCESSABLE_REQUEST;
        }

        return CreateFailure::CODE_UNKNOWN;
    }

    /**
     * @param CreateFailure::CODE_* $code
     *
     * @return CreateFailure::REASON_*
     */
    private function findReason(int $code): string
    {
        return self::REASONS[$code] ?? CreateFailure::REASON_UNKNOWN;
    }

    /**
     * @return array<string, string|int>
     */
    private function createContext(ExceptionInterface | UnsupportedProviderExceptionInterface $exception): array
    {
        if ($exception instanceof ApiLimitExceptionInterface) {
            return [
                'reset-timestamp' => $exception->getResetTimestamp(),
            ];
        }

        if ($exception instanceof CurlExceptionInterface) {
            return [
                'curl-code' => $exception->getCurlCode(),
            ];
        }

        if ($exception instanceof HttpExceptionInterface) {
            return [
                'status-code' => $exception->getStatusCode(),
            ];
        }

        if ($exception instanceof UnprocessableRequestExceptionInterface) {
            return [
                'provider-reason' => $exception->getReason(),
            ];
        }

        return [];
    }
}
