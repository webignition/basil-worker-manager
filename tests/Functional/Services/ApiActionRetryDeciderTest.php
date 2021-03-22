<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Model\MachineProviderActionInterface;
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
     * @param MachineProviderActionInterface::ACTION_* $action
     */
    public function testDecide(string $provider, string $action, \Throwable $exception, bool $expectedDecision): void
    {
        self::assertSame($expectedDecision, $this->decider->decide($provider, $action, $exception));
    }

    /**
     * @return array[]
     */
    public function decideDataProvider(): array
    {
        return [
            'digitalocean ' . ApiLimitExceededException::class => [
                'provider' => ProviderInterface::NAME_DIGITALOCEAN,
                'action' => MachineProviderActionInterface::ACTION_GET,
                'exception' => new ApiLimitExceededException(),
                'expectedDecision' => false,
            ],
            'digitalocean ' . InvalidArgumentException::class => [
                'provider' => ProviderInterface::NAME_DIGITALOCEAN,
                'action' => MachineProviderActionInterface::ACTION_GET,
                'exception' => new InvalidArgumentException(),
                'expectedDecision' => true,
            ],
        ];
    }
}
