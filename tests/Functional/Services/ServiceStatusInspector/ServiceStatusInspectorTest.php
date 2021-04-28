<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\ServiceStatusInspector;

use App\Services\ServiceStatusInspector\ComponentInspectorInterface;
use App\Services\ServiceStatusInspector\ServiceStatusInspector;
use App\Tests\AbstractBaseFunctionalTest;
use Mockery\MockInterface;
use webignition\ObjectReflector\ObjectReflector;

class ServiceStatusInspectorTest extends AbstractBaseFunctionalTest
{
    private ServiceStatusInspector $serviceStatusInspector;

    protected function setUp(): void
    {
        parent::setUp();

        $serviceStatusInspector = self::$container->get(ServiceStatusInspector::class);
        \assert($serviceStatusInspector instanceof ServiceStatusInspector);
        $this->serviceStatusInspector = $serviceStatusInspector;
    }

    /**
     * @dataProvider getDataProvider
     *
     * @param ComponentInspectorInterface[] $modifiedComponentInspectors
     * @param array<string, bool> $expectedServiceStatus
     */
    public function testGet(array $modifiedComponentInspectors, array $expectedServiceStatus): void
    {
        foreach ($modifiedComponentInspectors as $name => $componentInspector) {
            $this->setComponentInspector($name, $componentInspector);
        }

        self::assertEquals($expectedServiceStatus, $this->serviceStatusInspector->get());
    }

    /**
     * @return array[]
     */
    public function getDataProvider(): array
    {
        return [
            'all services available' => [
                'modifiedComponentInspectors' => [],
                'expectedServiceStatus' => [
                    'database' => true,
                    'message_queue' => true,
                ],
            ],
            'database unavailable' => [
                'modifiedComponentInspectors' => [
                    'database' => $this->createComponentInspectorThrowingException(),
                ],
                'expectedServiceStatus' => [
                    'database' => false,
                    'message_queue' => true,
                ],
            ],
            'message queue unavailable' => [
                'modifiedComponentInspectors' => [
                    'message_queue' => $this->createComponentInspectorThrowingException(),
                ],
                'expectedServiceStatus' => [
                    'database' => true,
                    'message_queue' => false,
                ],
            ],
            'all services unavailable' => [
                'modifiedComponentInspectors' => [
                    'database' => $this->createComponentInspectorThrowingException(),
                    'message_queue' => $this->createComponentInspectorThrowingException(),
                ],
                'expectedServiceStatus' => [
                    'database' => false,
                    'message_queue' => false,
                ],
            ],
        ];
    }

    private function setComponentInspector(string $name, ComponentInspectorInterface $componentInspector): void
    {
        $componentInspectors = ObjectReflector::getProperty($this->serviceStatusInspector, 'componentInspectors');

        if (array_key_exists($name, $componentInspectors)) {
            $componentInspectors[$name] = $componentInspector;
        }

        ObjectReflector::setProperty(
            $this->serviceStatusInspector,
            ServiceStatusInspector::class,
            'componentInspectors',
            $componentInspectors
        );
    }

    private function createComponentInspectorThrowingException(): ComponentInspectorInterface
    {
        $componentInspector = $this->createComponentInspector();
        if ($componentInspector instanceof MockInterface) {
            $componentInspector
                ->shouldReceive('__invoke')
                ->andThrow(new \Exception());
        }

        return $componentInspector;
    }

    private function createComponentInspector(): ComponentInspectorInterface
    {
        return \Mockery::mock(ComponentInspectorInterface::class);
    }
}
