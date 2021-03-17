<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Model\ProviderInterface;
use App\Services\ApiActionRetryDecider;
use App\Tests\AbstractBaseFunctionalTest;
use DigitalOceanV2\Exception\ApiLimitExceededException;
use DigitalOceanV2\Exception\InvalidArgumentException;

class ApiActionRetryDeciderTest extends AbstractBaseFunctionalTest
{
    private ApiActionRetryDecider $decider;

    protected function setUp(): void
    {
        parent::setUp();

        $decider = self::$container->get(ApiActionRetryDecider::class);
        if ($decider instanceof ApiActionRetryDecider) {
            $this->decider = $decider;
        }
    }

    /**
     * @dataProvider decideDataProvider
     *
     * @param ProviderInterface::NAME_* $provider
     */
    public function testDecide(string $provider, \Throwable $exception, bool $expectedDecision): void
    {
        self::assertSame($expectedDecision, $this->decider->decide($provider, $exception));
    }

    /**
     * @return array[]
     */
    public function decideDataProvider(): array
    {
        return [
            'digitalocean ' . ApiLimitExceededException::class => [
                'provider' => ProviderInterface::NAME_DIGITALOCEAN,
                'exception' => new ApiLimitExceededException(),
                'expectedDecision' => false,
            ],
            'digitalocean ' . InvalidArgumentException::class => [
                'provider' => ProviderInterface::NAME_DIGITALOCEAN,
                'exception' => new InvalidArgumentException(),
                'expectedDecision' => true,
            ],
        ];
    }
}
