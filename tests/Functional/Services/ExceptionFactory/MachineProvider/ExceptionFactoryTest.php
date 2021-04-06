<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\ExceptionFactory\MachineProvider;

use App\Exception\MachineProvider\CurlException;
use App\Exception\MachineProvider\DigitalOcean\HttpException;
use App\Services\ExceptionFactory\MachineProvider\ExceptionFactory;
use App\Tests\AbstractBaseFunctionalTest;
use DigitalOceanV2\Exception\RuntimeException;
use GuzzleHttp\Exception\ConnectException;
use Psr\Http\Message\RequestInterface;
use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\ExceptionInterface;
use webignition\BasilWorkerManagerInterfaces\RemoteRequestActionInterface;

class ExceptionFactoryTest extends AbstractBaseFunctionalTest
{
    private const ID = 'resource_id';
    private const ACTION = RemoteRequestActionInterface::ACTION_CREATE;

    private ExceptionFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();

        $factory = self::$container->get(ExceptionFactory::class);
        if ($factory instanceof ExceptionFactory) {
            $this->factory = $factory;
        }
    }

    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(\Throwable $exception, ExceptionInterface $expectedException): void
    {
        self::assertEquals(
            $expectedException,
            $this->factory->create(self::ID, self::ACTION, $exception)
        );
    }

    /**
     * @return array[]
     */
    public function createDataProvider(): array
    {
        $runtimeException = new RuntimeException('message', 400);
        $connectException = new ConnectException(
            'cURL error 7: Further non-relevant information including "cURL error: 88"',
            \Mockery::mock(RequestInterface::class)
        );

        return [
            RuntimeException::class . ' 400' => [
                'exception' => $runtimeException,
                'expectedException' => new HttpException(self::ID, self::ACTION, $runtimeException),
            ],
            ConnectException::class => [
                'exception' => $connectException,
                'expectedException' => new CurlException(7, self::ID, self::ACTION, $connectException),
            ],
        ];
    }
}
