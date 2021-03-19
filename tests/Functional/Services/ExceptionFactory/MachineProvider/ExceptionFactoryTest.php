<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\ExceptionFactory\MachineProvider;

use App\Exception\MachineProvider\DigitalOcean\HttpException;
use App\Exception\MachineProvider\ExceptionInterface;
use App\Model\MachineProviderActionInterface;
use App\Services\ExceptionFactory\MachineProvider\ExceptionFactory;
use App\Tests\AbstractBaseFunctionalTest;
use DigitalOceanV2\Exception\ExceptionInterface as VendorExceptionInterface;
use DigitalOceanV2\Exception\RuntimeException;

class ExceptionFactoryTest extends AbstractBaseFunctionalTest
{
    private const RESOURCE_ID = 'resource_id';

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
    public function testCreate(VendorExceptionInterface $exception, ExceptionInterface $expectedException): void
    {
        self::assertEquals(
            $expectedException,
            $this->factory->create(self::RESOURCE_ID, MachineProviderActionInterface::ACTION_CREATE, $exception)
        );
    }

    /**
     * @return array[]
     */
    public function createDataProvider(): array
    {
        $runtimeException400 = new RuntimeException('message', 400);

        return [
            RuntimeException::class . ' 400' => [
                'exception' => $runtimeException400,
                'expectedException' => new HttpException(
                    self::RESOURCE_ID,
                    MachineProviderActionInterface::ACTION_CREATE,
                    0,
                    $runtimeException400
                ),
            ],
        ];
    }
}
