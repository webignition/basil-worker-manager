<?php

namespace App\Services;

use App\Message\RemoteMachineRequestInterface;
use App\Model\RemoteRequestActionInterface;
use App\Services\RemoteRequestRetryDecider\RemoteRequestRetryDeciderInterface;
use webignition\BasilWorkerManagerInterfaces\ProviderInterface;

class RemoteRequestRetryDecider
{
    /**
     * @var RemoteRequestRetryDeciderInterface[]
     */
    private array $deciders;

    /**
     * @var array<RemoteRequestActionInterface::ACTION_*, int>
     */
    private array $retryLimits = [];

    /**
     * @param RemoteRequestRetryDeciderInterface[] $deciders
     * @param array<RemoteRequestActionInterface::ACTION_*, int> $retryLimits
     */
    public function __construct(
        array $deciders,
        array $retryLimits,
    ) {
        $this->deciders = array_filter($deciders, function ($item) {
            return $item instanceof RemoteRequestRetryDeciderInterface;
        });

        foreach ($retryLimits as $key => $value) {
            if (in_array($key, RemoteRequestActionInterface::ALL)) {
                $this->retryLimits[$key] = $value;
            }
        }
    }

    /**
     * @param ProviderInterface::NAME_* $provider
     */
    public function decide(string $provider, RemoteMachineRequestInterface $request, \Throwable $exception): bool
    {
        $action = $request->getAction();

        $retryLimit = $this->retryLimits[$action] ?? 0;

        foreach ($this->deciders as $decider) {
            if ($decider->handles($provider)) {
                return $request->getRetryCount() < $retryLimit && $decider->decide($action, $exception);
            }
        }

        return false;
    }
}
