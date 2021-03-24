<?php

namespace App\Services;

use App\Model\ProviderInterface;
use App\Model\RemoteRequestActionInterface;
use App\Services\RemoteRequestRetryDecider\RemoteRequestRetryDeciderInterface;

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
     * @param RemoteRequestActionInterface::ACTION_* $action
     */
    public function decide(string $provider, string $action, int $retryCount, \Throwable $exception): bool
    {
        $retryLimit = $this->retryLimits[$action] ?? 0;

        foreach ($this->deciders as $decider) {
            if ($decider->handles($provider)) {
                return $retryCount < $retryLimit && $decider->decide($action, $exception);
            }
        }

        return false;
    }
}
