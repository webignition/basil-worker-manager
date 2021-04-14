<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Exception\MachineNotFoundException;
use App\Exception\MachineProvider\DigitalOcean\HttpException;
use App\Model\DigitalOcean\RemoteMachine;
use App\Services\RemoteMachineFinder;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\HttpResponseFactory;
use DigitalOceanV2\Entity\Droplet as DropletEntity;
use DigitalOceanV2\Exception\RuntimeException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use webignition\BasilWorkerManagerInterfaces\RemoteRequestActionInterface;

class RemoteMachineFinderTest extends AbstractBaseFunctionalTest
{
    private const MACHINE_ID = 'machine id';

    private RemoteMachineFinder $finder;
    private MockHandler $mockHandler;

    protected function setUp(): void
    {
        parent::setUp();

        $machineManager = self::$container->get(RemoteMachineFinder::class);
        \assert($machineManager instanceof RemoteMachineFinder);
        $this->finder = $machineManager;

        $mockHandler = self::$container->get(MockHandler::class);
        if ($mockHandler instanceof MockHandler) {
            $this->mockHandler = $mockHandler;
        }
    }

    public function testFindRemoteMachineSuccess(): void
    {
        $dropletEntity = new DropletEntity([
            'id' => 123,
            'status' => RemoteMachine::STATE_NEW,
        ]);

        $this->mockHandler->append(HttpResponseFactory::fromDropletEntityCollection([$dropletEntity]));

        $remoteMachine = $this->finder->find(self::MACHINE_ID);

        self::assertEquals(new RemoteMachine($dropletEntity), $remoteMachine);
    }

    /**
     * @dataProvider findRemoteMachineThrowsMachineNotFoundExceptionDataProvider
     *
     * @param ResponseInterface[] $apiResponses
     * @param \Throwable[] $expectedExceptionStack
     */
    public function testFindRemoteMachineThrowsMachineNotFoundException(
        array $apiResponses,
        array $expectedExceptionStack
    ): void {
        $this->mockHandler->append(...$apiResponses);

        try {
            $this->finder->find(self::MACHINE_ID);
            self::fail(MachineNotFoundException::class . ' not thrown');
        } catch (MachineNotFoundException $machineNotFoundException) {
            self::assertEquals($expectedExceptionStack, $machineNotFoundException->getExceptionStack());
        }
    }

    /**
     * @return array[]
     */
    public function findRemoteMachineThrowsMachineNotFoundExceptionDataProvider(): array
    {
        return [
            'machine does not exist' => [
                'apiResponses' => [
                    HttpResponseFactory::fromDropletEntityCollection([]),
                ],
                'expectedExceptionStack' => [],
            ],
            'request failed' => [
                'apiResponses' => [
                    new Response(503),
                ],
                'expectedExceptionStack' => [
                    new HttpException(
                        self::MACHINE_ID,
                        RemoteRequestActionInterface::ACTION_GET,
                        new RuntimeException('Service Unavailable', 503)
                    ),
                ],
            ],
        ];
    }
}
