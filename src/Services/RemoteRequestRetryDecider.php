<?php

namespace App\Services;

use App\Message\RemoteMachineMessageInterface;
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
     */
    public function __construct(
        array $deciders,
    ) {
        $this->deciders = array_filter($deciders, function ($item) {
            return $item instanceof RemoteRequestRetryDeciderInterface;
        });
    }

    /**
     * @param ProviderInterface::NAME_* $provider
     */
    public function decide(string $provider, RemoteMachineMessageInterface $request, \Throwable $exception): bool
    {
        foreach ($this->deciders as $decider) {
            if ($decider->handles($provider)) {
                return $decider->decide($request->getAction(), $exception);
            }
        }

        return false;
    }
}
