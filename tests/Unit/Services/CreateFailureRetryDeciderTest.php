<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Model\ProviderInterface;
use App\Services\CreateFailureRetryDecider;
use App\Services\CreateFailureRetryDecider\DigitalOcean\DigitalOceanCreateFailureRetryDecider;
use DigitalOceanV2\Exception\InvalidArgumentException;
use DigitalOceanV2\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

class CreateFailureRetryDeciderTest extends TestCase
{
    /**
     * @dataProvider decideDataProvider
     *
     * @param ProviderInterface::NAME_* $provider
     */
    public function testDecide(
        CreateFailureRetryDecider $decider,
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
                'decider' => new CreateFailureRetryDecider([]),
                'provider' => ProviderInterface::NAME_DIGITALOCEAN,
                'exception' => new \Exception(),
                'expectedDecision' => false,
            ],
            'has decider, false' => [
                'decider' => new CreateFailureRetryDecider([
                    new DigitalOceanCreateFailureRetryDecider(),
                ]),
                'provider' => ProviderInterface::NAME_DIGITALOCEAN,
                'exception' => new ValidationFailedException(),
                'expectedDecision' => false,
            ],
            'has decider, true' => [
                'decider' => new CreateFailureRetryDecider([
                    new DigitalOceanCreateFailureRetryDecider(),
                ]),
                'provider' => ProviderInterface::NAME_DIGITALOCEAN,
                'exception' => new InvalidArgumentException(),
                'expectedDecision' => true,
            ],
        ];
    }
}
