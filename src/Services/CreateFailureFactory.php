<?php

namespace App\Services;

use App\Entity\CreateFailure;
use App\Entity\Machine;
use App\Exception\MachineProvider\ApiLimitExceptionInterface;
use App\Exception\MachineProvider\AuthenticationExceptionInterface;
use App\Exception\MachineProvider\CurlExceptionInterface;
use App\Exception\MachineProvider\ExceptionInterface;
use App\Exception\MachineProvider\HttpExceptionInterface;
use App\Exception\MachineProvider\UnprocessableRequestExceptionInterface;
use App\Exception\UnsupportedProviderException;
use Doctrine\ORM\EntityManagerInterface;

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
        private EntityManagerInterface $entityManager
    ) {
    }

    public function create(
        Machine $machine,
        ExceptionInterface | UnsupportedProviderException $exception
    ): CreateFailure {
        $existingEntity = $this->entityManager->find(CreateFailure::class, $machine->getId());
        if ($existingEntity instanceof CreateFailure) {
            return $existingEntity;
        }

        $code = $this->findCode($exception);

        $entity = CreateFailure::create(
            $machine,
            $code,
            $this->findReason($code),
            $this->createContext($exception)
        );

        $this->entityManager->persist($entity);
        $this->entityManager->flush();

        return $entity;
    }

    /**
     * @param ExceptionInterface|UnsupportedProviderException $exception
     *
     * @return CreateFailure::CODE_*
     */
    private function findCode(ExceptionInterface | UnsupportedProviderException $exception): int
    {
        if ($exception instanceof UnsupportedProviderException) {
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
    private function createContext(ExceptionInterface | UnsupportedProviderException $exception): array
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
