<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Model\ProviderInterface;
use App\Model\RemoteRequestActionInterface;
use App\Services\RemoteRequestRetryDecider;
use App\Services\RemoteRequestRetryDecider\DigitalOcean\DigitalOceanRemoteRequestRetryDecider;
use DigitalOceanV2\Exception\ApiLimitExceededException;
use DigitalOceanV2\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RemoteRequestRetryDeciderTest extends TestCase
{
    /**
     * @dataProvider decideDataProvider
     *
     * @param ProviderInterface::NAME_* $provider
     * @param RemoteRequestActionInterface::ACTION_* $action
     */
    public function testDecide(
        RemoteRequestRetryDecider $decider,
        string $provider,
        string $action,
        int $retryCount,
        \Throwable $exception,
        bool $expectedDecision
    ): void {
        self::assertSame($expectedDecision, $decider->decide($provider, $action, $retryCount, $exception));
    }

    /**
     * @return array[]
     */
    public function decideDataProvider(): array
    {
        return [
            'no deciders' => [
                'decider' => new RemoteRequestRetryDecider([], []),
                'provider' => ProviderInterface::NAME_DIGITALOCEAN,
                'action' => RemoteRequestActionInterface::ACTION_GET,
                'retryCount' => 0,
                'exception' => new \Exception(),
                'expectedDecision' => false,
            ],
            'has decider, decider: false, retry limit not reached' => [
                'decider' => new RemoteRequestRetryDecider(
                    [
                        new DigitalOceanRemoteRequestRetryDecider(),
                    ],
                    [
                        RemoteRequestActionInterface::ACTION_GET => 10,
                    ]
                ),
                'provider' => ProviderInterface::NAME_DIGITALOCEAN,
                'action' => RemoteRequestActionInterface::ACTION_GET,
                'retryCount' => 0,
                'exception' => new ApiLimitExceededException(),
                'expectedDecision' => false,
            ],
            'has decider, decider: true, retry limit not reached' => [
                'decider' => new RemoteRequestRetryDecider(
                    [
                        new DigitalOceanRemoteRequestRetryDecider(),
                    ],
                    [
                        RemoteRequestActionInterface::ACTION_GET => 10,
                    ]
                ),
                'provider' => ProviderInterface::NAME_DIGITALOCEAN,
                'action' => RemoteRequestActionInterface::ACTION_GET,
                'retryCount' => 0,
                'exception' => new InvalidArgumentException(),
                'expectedDecision' => true,
            ],
            'has decider, decider: true, retry limit reached' => [
                'decider' => new RemoteRequestRetryDecider(
                    [
                        new DigitalOceanRemoteRequestRetryDecider(),
                    ],
                    [
                        RemoteRequestActionInterface::ACTION_GET => 1,
                    ]
                ),
                'provider' => ProviderInterface::NAME_DIGITALOCEAN,
                'action' => RemoteRequestActionInterface::ACTION_GET,
                'retryCount' => 1,
                'exception' => new InvalidArgumentException(),
                'expectedDecision' => false,
            ],
        ];
    }
}
