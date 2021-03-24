<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Model\MachineProviderActionInterface;
use App\Model\ProviderInterface;
use App\Services\RemoteRequestRetryDecider;
use App\Tests\AbstractBaseFunctionalTest;
use DigitalOceanV2\Exception\ApiLimitExceededException;
use DigitalOceanV2\Exception\InvalidArgumentException;

class RemoteRequestRetryDeciderTest extends AbstractBaseFunctionalTest
{
    private RemoteRequestRetryDecider $decider;

    protected function setUp(): void
    {
        parent::setUp();

        $decider = self::$container->get(RemoteRequestRetryDecider::class);
        if ($decider instanceof RemoteRequestRetryDecider) {
            $this->decider = $decider;
        }
    }

    /**
     * @dataProvider decideDataProvider
     *
     * @param ProviderInterface::NAME_* $provider
     * @param MachineProviderActionInterface::ACTION_* $action
     */
    public function testDecide(
        string $provider,
        string $action,
        int $retryCount,
        \Throwable $exception,
        bool $expectedDecision
    ): void {
        self::assertSame($expectedDecision, $this->decider->decide($provider, $action, $retryCount, $exception));
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
                'retryCount' => 0,
                'exception' => new ApiLimitExceededException(),
                'expectedDecision' => false,
            ],
            'digitalocean ' . InvalidArgumentException::class => [
                'provider' => ProviderInterface::NAME_DIGITALOCEAN,
                'action' => MachineProviderActionInterface::ACTION_GET,
                'retryCount' => 0,
                'exception' => new InvalidArgumentException(),
                'expectedDecision' => true,
            ],
        ];
    }
}
