<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Message\CreateMachine;
use App\Message\DeleteMachine;
use App\Message\GetMachine;
use App\Message\MachineExists;
use App\Message\RetryableRequestInterface;
use App\Services\RemoteRequestRetryCounter;
use App\Tests\AbstractBaseFunctionalTest;

class RemoteRequestRetryCounterTest extends AbstractBaseFunctionalTest
{
    private RemoteRequestRetryCounter $counter;

    protected function setUp(): void
    {
        parent::setUp();

        $counter = self::$container->get(RemoteRequestRetryCounter::class);
        \assert($counter instanceof RemoteRequestRetryCounter);
        $this->counter = $counter;
    }

    /**
     * @dataProvider isLimitReachedDataProvider
     */
    public function testIsLimitReached(
        RetryableRequestInterface $request,
        bool $expectedIsLimitReached
    ): void {
        self::assertSame($expectedIsLimitReached, $this->counter->isLimitReached($request));
    }

    /**
     * @return array[]
     */
    public function isLimitReachedDataProvider(): array
    {
        return [
            CreateMachine::class . ' limit not reached' => [
                'request' => new CreateMachine('id'),
                'expectedIsLimitReached' => false,
            ],
            CreateMachine::class . ' limit reached' => [
                'request' => (new CreateMachine('id'))
                    ->incrementRetryCount()
                    ->incrementRetryCount()
                    ->incrementRetryCount(),
                'expectedIsLimitReached' => true,
            ],
            DeleteMachine::class . ' limit not reached' => [
                'request' => new DeleteMachine('id'),
                'expectedIsLimitReached' => false,
            ],
            DeleteMachine::class . ' limit reached' => [
                'request' => (new DeleteMachine('id'))
                    ->incrementRetryCount()
                    ->incrementRetryCount()
                    ->incrementRetryCount()
                    ->incrementRetryCount()
                    ->incrementRetryCount()
                    ->incrementRetryCount()
                    ->incrementRetryCount()
                    ->incrementRetryCount()
                    ->incrementRetryCount()
                    ->incrementRetryCount(),
                'expectedIsLimitReached' => true,
            ],
            GetMachine::class . ' limit not reached' => [
                'request' => new GetMachine('id'),
                'expectedIsLimitReached' => false,
            ],
            GetMachine::class . ' limit reached' => [
                'request' => (new GetMachine('id'))
                    ->incrementRetryCount()
                    ->incrementRetryCount()
                    ->incrementRetryCount()
                    ->incrementRetryCount()
                    ->incrementRetryCount()
                    ->incrementRetryCount()
                    ->incrementRetryCount()
                    ->incrementRetryCount()
                    ->incrementRetryCount()
                    ->incrementRetryCount(),
                'expectedIsLimitReached' => true,
            ],
            MachineExists::class . ' limit not reached' => [
                'request' => new MachineExists('id'),
                'expectedIsLimitReached' => false,
            ],
            MachineExists::class . ' limit reached' => [
                'request' => (new MachineExists('id'))
                    ->incrementRetryCount()
                    ->incrementRetryCount()
                    ->incrementRetryCount()
                    ->incrementRetryCount()
                    ->incrementRetryCount()
                    ->incrementRetryCount()
                    ->incrementRetryCount()
                    ->incrementRetryCount()
                    ->incrementRetryCount()
                    ->incrementRetryCount(),
                'expectedIsLimitReached' => true,
            ],
        ];
    }
}
