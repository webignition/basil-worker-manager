<?php

declare(strict_types=1);

namespace App\Tests\Functional\MessageDispatcher;

use App\Message\CreateMachine;
use App\Message\DeleteMachine;
use App\Message\GetMachine;
use App\Message\MachineExists;
use App\Message\RetryableMessageInterface;
use App\MessageDispatcher\NonDispatchableMessageException;
use App\MessageDispatcher\RetryByLimitMiddleware;
use App\Tests\AbstractBaseFunctionalTest;
use Symfony\Component\Messenger\Envelope;

class RetryByLimitMiddlewareTest extends AbstractBaseFunctionalTest
{
    private RetryByLimitMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $middleware = self::$container->get(RetryByLimitMiddleware::class);
        \assert($middleware instanceof RetryByLimitMiddleware);
        $this->middleware = $middleware;
    }

    /**
     * @dataProvider invokeNoExceptionDataProvider
     */
    public function testInvokeNoException(RetryableMessageInterface $request): void
    {
        ($this->middleware)(Envelope::wrap($request));
        self::expectNotToPerformAssertions();
    }

    /**
     * @return array[]
     */
    public function invokeNoExceptionDataProvider(): array
    {
        return [
            CreateMachine::class . ' limit not reached' => [
                'request' => new CreateMachine('id'),
            ],
            DeleteMachine::class . ' limit not reached' => [
                'request' => new DeleteMachine('id'),
            ],
            GetMachine::class . ' limit not reached' => [
                'request' => new GetMachine('id'),
            ],
            MachineExists::class . ' limit not reached' => [
                'request' => new MachineExists('id'),
            ],
        ];
    }

    /**
     * @dataProvider invokeHasExceptionDataProvider
     */
    public function testInvokeHasException(
        RetryableMessageInterface $request,
    ): void {
        $this->expectExceptionObject(new NonDispatchableMessageException($request));

        ($this->middleware)(Envelope::wrap($request));
    }

    /**
     * @return array[]
     */
    public function invokeHasExceptionDataProvider(): array
    {
        return [
            CreateMachine::class . ' limit reached' => [
                'request' => (new CreateMachine('id'))
                    ->incrementRetryCount()
                    ->incrementRetryCount()
                    ->incrementRetryCount()
                    ->incrementRetryCount(),
                'expectedIsLimitReached' => true,
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
                    ->incrementRetryCount()
                    ->incrementRetryCount(),
                'expectedIsLimitReached' => true,
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
                    ->incrementRetryCount()
                    ->incrementRetryCount(),
                'expectedIsLimitReached' => true,
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
                    ->incrementRetryCount()
                    ->incrementRetryCount(),
                'expectedIsLimitReached' => true,
            ],
        ];
    }
}
