<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Model\MachineProviderActionInterface;
use App\Model\ProviderInterface;
use App\Services\ApiActionRetryDecider;
use App\Services\ApiActionRetryDecider\DigitalOcean\DigitalOceanApiActionRetryDecider;
use DigitalOceanV2\Exception\ApiLimitExceededException;
use DigitalOceanV2\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ApiActionRetryDeciderTest extends TestCase
{
    /**
     * @dataProvider decideDataProvider
     *
     * @param ProviderInterface::NAME_* $provider
     * @param MachineProviderActionInterface::ACTION_* $action
     */
    public function testDecide(
        ApiActionRetryDecider $decider,
        string $provider,
        string $action,
        \Throwable $exception,
        bool $expectedDecision
    ): void {
        self::assertSame($expectedDecision, $decider->decide($provider, $action, $exception));
    }

    /**
     * @return array[]
     */
    public function decideDataProvider(): array
    {
        return [
            'no deciders' => [
                'decider' => new ApiActionRetryDecider([]),
                'provider' => ProviderInterface::NAME_DIGITALOCEAN,
                'action' => MachineProviderActionInterface::ACTION_GET,
                'exception' => new \Exception(),
                'expectedDecision' => false,
            ],
            'has decider, false' => [
                'decider' => new ApiActionRetryDecider([
                    new DigitalOceanApiActionRetryDecider(),
                ]),
                'provider' => ProviderInterface::NAME_DIGITALOCEAN,
                'action' => MachineProviderActionInterface::ACTION_GET,
                'exception' => new ApiLimitExceededException(),
                'expectedDecision' => false,
            ],
            'has decider, true' => [
                'decider' => new ApiActionRetryDecider([
                    new DigitalOceanApiActionRetryDecider(),
                ]),
                'provider' => ProviderInterface::NAME_DIGITALOCEAN,
                'action' => MachineProviderActionInterface::ACTION_GET,
                'exception' => new InvalidArgumentException(),
                'expectedDecision' => true,
            ],
        ];
    }
}
