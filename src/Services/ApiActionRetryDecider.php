<?php

namespace App\Services;

use App\Model\MachineProviderActionInterface;
use App\Model\ProviderInterface;
use App\Services\ApiActionRetryDecider\ApiActionRetryDeciderInterface;

class ApiActionRetryDecider
{
    /**
     * @var ApiActionRetryDeciderInterface[]
     */
    private array $deciders;

    /**
     * @var array<MachineProviderActionInterface::ACTION_*, int>
     */
    private array $retryLimits = [];

    /**
     * @param ApiActionRetryDeciderInterface[] $deciders
     * @param array<MachineProviderActionInterface::ACTION_*, int> $retryLimits
     */
    public function __construct(
        array $deciders,
        array $retryLimits,
    ) {
        $this->deciders = array_filter($deciders, function ($item) {
            return $item instanceof ApiActionRetryDeciderInterface;
        });

        foreach ($retryLimits as $key => $value) {
            if (in_array($key, MachineProviderActionInterface::ALL)) {
                $this->retryLimits[$key] = $value;
            }
        }
    }

    /**
     * @param ProviderInterface::NAME_* $provider
     * @param MachineProviderActionInterface::ACTION_* $action
     */
    public function decide(string $provider, string $action, int $retryCount, \Throwable $exception): bool
    {
        foreach ($this->deciders as $decider) {
            if ($decider->handles($provider)) {
                return $decider->decide($action, $exception);
            }
        }

        return false;
    }
}
