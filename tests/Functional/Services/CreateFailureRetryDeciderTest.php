<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Model\ProviderInterface;
use App\Services\CreateFailureRetryDecider;
use App\Tests\Functional\AbstractBaseFunctionalTest;
use DigitalOceanV2\Exception\InvalidArgumentException;
use DigitalOceanV2\Exception\ValidationFailedException;

class CreateFailureRetryDeciderTest extends AbstractBaseFunctionalTest
{
    private CreateFailureRetryDecider $decider;

    protected function setUp(): void
    {
        parent::setUp();

        $decider = self::$container->get(CreateFailureRetryDecider::class);
        if ($decider instanceof CreateFailureRetryDecider) {
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
            'digitalocean ' . ValidationFailedException::class => [
                'provider' => ProviderInterface::NAME_DIGITALOCEAN,
                'exception' => new ValidationFailedException(),
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
