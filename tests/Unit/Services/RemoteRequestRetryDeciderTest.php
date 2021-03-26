<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Message\RemoteMachineRequestInterface;
use App\Message\UpdateMachine;
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
     */
    public function testDecide(
        RemoteRequestRetryDecider $decider,
        string $provider,
        RemoteMachineRequestInterface $request,
        \Throwable $exception,
        bool $expectedDecision
    ): void {
        self::assertSame($expectedDecision, $decider->decide($provider, $request, $exception));
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
                'request' => new UpdateMachine('id'),
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
                'request' => new UpdateMachine('id'),
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
                'request' => new UpdateMachine('id'),
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
                'request' => (new UpdateMachine('id'))->incrementRetryCount(),
                'exception' => new InvalidArgumentException(),
                'expectedDecision' => false,
            ],
        ];
    }
}
