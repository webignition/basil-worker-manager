<?php

namespace App\Services;

use App\Message\RemoteMachineRequestInterface;
use App\Services\RemoteRequestRetryDecider\RemoteRequestRetryDeciderInterface;
use webignition\BasilWorkerManagerInterfaces\ProviderInterface;

class RemoteRequestRetryDecider
{
    /**
     * @var RemoteRequestRetryDeciderInterface[]
     */
    private array $deciders;

    /**
     * @param RemoteRequestRetryDeciderInterface[] $deciders
     * @param array<class-string, int> $retryLimits
     */
    public function __construct(
        array $deciders,
        private array $retryLimits,
    ) {
        $this->deciders = array_filter($deciders, function ($item) {
            return $item instanceof RemoteRequestRetryDeciderInterface;
        });
    }

    /**
     * @param ProviderInterface::NAME_* $provider
     */
    public function decide(string $provider, RemoteMachineRequestInterface $request, \Throwable $exception): bool
    {
        $retryLimit = $this->retryLimits[$request::class] ?? 0;

        foreach ($this->deciders as $decider) {
            if ($decider->handles($provider)) {
                return $request->getRetryCount() < $retryLimit && $decider->decide($request->getAction(), $exception);
            }
        }

        return false;
    }
}
