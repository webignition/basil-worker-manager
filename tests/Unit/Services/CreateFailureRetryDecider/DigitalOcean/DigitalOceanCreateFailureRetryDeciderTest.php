<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services\CreateFailureRetryDecider\DigitalOcean;

use App\Model\ProviderInterface;
use App\Services\CreateFailureRetryDecider\DigitalOcean\DigitalOceanCreateFailureRetryDecider;
use DigitalOceanV2\Exception\ApiLimitExceededException;
use DigitalOceanV2\Exception\DiscoveryFailedException;
use DigitalOceanV2\Exception\ErrorException;
use DigitalOceanV2\Exception\InvalidArgumentException;
use DigitalOceanV2\Exception\InvalidRecordException;
use DigitalOceanV2\Exception\RuntimeException;
use DigitalOceanV2\Exception\ValidationFailedException;
use PHPUnit\Framework\TestCase;

class DigitalOceanCreateFailureRetryDeciderTest extends TestCase
{
    private DigitalOceanCreateFailureRetryDecider $decider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->decider = new DigitalOceanCreateFailureRetryDecider();
    }

    public function testHandles(): void
    {
        self::assertTrue($this->decider->handles(ProviderInterface::NAME_DIGITALOCEAN));
    }

    /**
     * @dataProvider decideDataProvider
     */
    public function testDecide(\Throwable $exception, bool $expectedDecision): void
    {
        self::assertSame($expectedDecision, $this->decider->decide($exception));
    }

    /**
     * @return array[]
     */
    public function decideDataProvider(): array
    {
        return [
            ApiLimitExceededException::class => [
                'exception' => new ApiLimitExceededException(),
                'expectedDecision' => false,
            ],
            ValidationFailedException::class => [
                'exception' => new ValidationFailedException(),
                'expectedDecision' => false,
            ],
            RuntimeException::class => [
                'exception' => new RuntimeException(),
                'expectedDecision' => true,
            ],
            DiscoveryFailedException::class => [
                'exception' => new DiscoveryFailedException(),
                'expectedDecision' => true,
            ],
            ErrorException::class => [
                'exception' => new ErrorException(),
                'expectedDecision' => true,
            ],
            InvalidRecordException::class => [
                'exception' => new InvalidRecordException(),
                'expectedDecision' => true,
            ],
            InvalidArgumentException::class => [
                'exception' => new InvalidArgumentException(),
                'expectedDecision' => true,
            ],
        ];
    }
}