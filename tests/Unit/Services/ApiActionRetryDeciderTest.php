<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

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
     */
    public function testDecide(
        ApiActionRetryDecider $decider,
        string $provider,
        \Throwable $exception,
        bool $expectedDecision
    ): void {
        self::assertSame($expectedDecision, $decider->decide($provider, $exception));
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
                'exception' => new \Exception(),
                'expectedDecision' => false,
            ],
            'has decider, false' => [
                'decider' => new ApiActionRetryDecider([
                    new DigitalOceanApiActionRetryDecider(),
                ]),
                'provider' => ProviderInterface::NAME_DIGITALOCEAN,
                'exception' => new ApiLimitExceededException(),
                'expectedDecision' => false,
            ],
            'has decider, true' => [
                'decider' => new ApiActionRetryDecider([
                    new DigitalOceanApiActionRetryDecider(),
                ]),
                'provider' => ProviderInterface::NAME_DIGITALOCEAN,
                'exception' => new InvalidArgumentException(),
                'expectedDecision' => true,
            ],
        ];
    }
}
