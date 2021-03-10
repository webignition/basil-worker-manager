<?php

namespace App\Services;

use App\Model\ProviderInterface;
use App\Services\CreateFailureRetryDecider\CreateFailureRetryDeciderInterface;

class CreateFailureRetryDecider
{
    /**
     * @var CreateFailureRetryDeciderInterface[]
     */
    private array $deciders;

    /**
     * @param CreateFailureRetryDeciderInterface[] $deciders
     */
    public function __construct(
        array $deciders,
    ) {
        $this->deciders = array_filter($deciders, function ($item) {
            return $item instanceof CreateFailureRetryDeciderInterface;
        });
    }

    /**
     * @param ProviderInterface::NAME_* $provider
     */
    public function decide(string $provider, \Throwable $exception): bool
    {
        foreach ($this->deciders as $decider) {
            if ($decider->handles($provider)) {
                return $decider->decide($exception);
            }
        }

        return false;
    }
}
