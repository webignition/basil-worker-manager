<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services\ApiActionRetryDecider\DigitalOcean;

use App\Exception\MachineProvider\DigitalOcean\DropletLimitExceededException;
use App\Model\MachineProviderActionInterface;
use App\Model\ProviderInterface;
use App\Services\ApiActionRetryDecider\DigitalOcean\DigitalOceanApiActionRetryDecider;
use DigitalOceanV2\Exception\ApiLimitExceededException;
use DigitalOceanV2\Exception\DiscoveryFailedException;
use DigitalOceanV2\Exception\ErrorException;
use DigitalOceanV2\Exception\InvalidArgumentException;
use DigitalOceanV2\Exception\InvalidRecordException;
use DigitalOceanV2\Exception\RuntimeException;
use DigitalOceanV2\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

class DigitalOceanApiActionRetryDeciderTest extends TestCase
{
    private DigitalOceanApiActionRetryDecider $decider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->decider = new DigitalOceanApiActionRetryDecider();
    }

    public function testHandles(): void
    {
        self::assertTrue($this->decider->handles(ProviderInterface::NAME_DIGITALOCEAN));
    }

    /**
     * @dataProvider decideDataProvider
     *
     * @param MachineProviderActionInterface::ACTION_* $action
     */
    public function testDecide(\Throwable $exception, string $action, int $retryCount, bool $expectedDecision): void
    {
        self::assertSame($expectedDecision, $this->decider->decide($action, $retryCount, $exception));
    }

    /**
     * @return array[]
     */
    public function decideDataProvider(): array
    {
        return [
            ApiLimitExceededException::class => [
                'exception' => new ApiLimitExceededException(),
                'action' => MachineProviderActionInterface::ACTION_GET,
                'retryCount' => 0,
                'expectedDecision' => false,
            ],
            ValidationFailedException::class => [
                'exception' => new ValidationFailedException(),
                'action' => MachineProviderActionInterface::ACTION_GET,
                'retryCount' => 0,
                'expectedDecision' => true,
            ],
            RuntimeException::class . ' non-401' => [
                'exception' => new RuntimeException(),
                'action' => MachineProviderActionInterface::ACTION_GET,
                'retryCount' => 0,
                'expectedDecision' => true,
            ],
            RuntimeException::class . ' 401' => [
                'exception' => new RuntimeException('message', 401),
                'action' => MachineProviderActionInterface::ACTION_GET,
                'retryCount' => 0,
                'expectedDecision' => false,
            ],
            DiscoveryFailedException::class => [
                'exception' => new DiscoveryFailedException(),
                'action' => MachineProviderActionInterface::ACTION_GET,
                'retryCount' => 0,
                'expectedDecision' => true,
            ],
            ErrorException::class => [
                'exception' => new ErrorException(),
                'action' => MachineProviderActionInterface::ACTION_GET,
                'retryCount' => 0,
                'expectedDecision' => true,
            ],
            InvalidRecordException::class => [
                'exception' => new InvalidRecordException(),
                'action' => MachineProviderActionInterface::ACTION_GET,
                'retryCount' => 0,
                'expectedDecision' => true,
            ],
            InvalidArgumentException::class => [
                'exception' => new InvalidArgumentException(),
                'action' => MachineProviderActionInterface::ACTION_GET,
                'retryCount' => 0,
                'expectedDecision' => true,
            ],
            DropletLimitExceededException::class => [
                'exception' => \Mockery::mock(DropletLimitExceededException::class),
                'action' => MachineProviderActionInterface::ACTION_GET,
                'retryCount' => 0,
                'expectedDecision' => false,
            ],
        ];
    }
}
