<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services\ServiceStatusInspector;

use App\Entity\CreateFailure;
use App\Entity\Machine;
use App\Entity\MachineProvider;
use App\Model\ServiceStatus\ComponentStatus;
use App\Model\ServiceStatus\ComponentStatusInterface;
use App\Services\ServiceStatusInspector\ComponentInspector;
use App\Services\ServiceStatusInspector\ComponentInspectorInterface;
use App\Services\ServiceStatusInspector\DatabaseInspectionHandler;
use App\Tests\AbstractBaseFunctionalTest;
use App\Tests\Services\DoctrineExceptionFactory;
use Doctrine\ORM\EntityManagerInterface;
use Mockery\MockInterface;
use webignition\ObjectReflector\ObjectReflector;

class ComponentInspectorTest extends AbstractBaseFunctionalTest
{
    /**
     * @dataProvider databaseComponentInspectorAuthenticationFailureDataProvider
     * @dataProvider databaseComponentInspectorFieldNotFoundDataProvider
     * @dataProvider databaseComponentInspectorTableNotFoundDataProvider
     * @dataProvider databaseComponentInspectorUnknownConnectionExceptionDataProvider
     * @dataProvider databaseComponentInspectorAvailableDataProvider
     */
    public function testDatabaseComponentInspector(
        EntityManagerInterface $entityManager,
        ComponentStatusInterface $expectedStatus
    ): void {
        $componentInspector = self::$container->get('app.services.service_status_inspector.component.database');
        self::assertInstanceOf(ComponentInspectorInterface::class, $componentInspector);

        self::assertSame(
            self::$container->get(DatabaseInspectionHandler::class),
            ObjectReflector::getProperty($componentInspector, 'inspector')
        );

        $inspector = new DatabaseInspectionHandler($entityManager);
        ObjectReflector::setProperty($componentInspector, ComponentInspector::class, 'inspector', $inspector);

        $status = $componentInspector->getStatus();

        self::assertEquals($expectedStatus, $status);
    }

    /**
     * @return array[]
     */
    public function databaseComponentInspectorAvailableDataProvider(): array
    {
        return [
            'available' => [
                'entityManager' => $this->createEntityManager(),
                'expectedStatus' => new ComponentStatus('database'),
            ],
        ];
    }

    /**
     * @return array[]
     */
    public function databaseComponentInspectorAuthenticationFailureDataProvider(): array
    {
        $expectedStatus = (new ComponentStatus('database'))
            ->withUnavailable()
            ->withUnavailableReason('authentication failure');

        return $this->createExceptionDataProvider(
            'authentication failure',
            $expectedStatus,
            DoctrineExceptionFactory::createAuthenticationException()
        );
    }

    /**
     * @return array[]
     */
    public function databaseComponentInspectorFieldNotFoundDataProvider(): array
    {
        $expectedStatus = (new ComponentStatus('database'))
            ->withUnavailable()
            ->withUnavailableReason('field not found');

        return $this->createExceptionDataProvider(
            'field not found',
            $expectedStatus,
            DoctrineExceptionFactory::createInvalidFieldNameException()
        );
    }

    /**
     * @return array[]
     */
    public function databaseComponentInspectorTableNotFoundDataProvider(): array
    {
        $expectedStatus = (new ComponentStatus('database'))
            ->withUnavailable()
            ->withUnavailableReason('table not found');

        return $this->createExceptionDataProvider(
            'table not found',
            $expectedStatus,
            DoctrineExceptionFactory::createTableNotFoundException()
        );
    }

    /**
     * @return array[]
     */
    public function databaseComponentInspectorDatabaseNotFoundDataProvider(): array
    {
        $expectedStatus = (new ComponentStatus('database'))
            ->withUnavailable()
            ->withUnavailableReason('database not found');

        return $this->createExceptionDataProvider(
            'database not found',
            $expectedStatus,
            DoctrineExceptionFactory::createDatabaseDoesNotExistException()
        );
    }

    /**
     * @return array[]
     */
    public function databaseComponentInspectorUnknownConnectionExceptionDataProvider(): array
    {
        $expectedStatus = (new ComponentStatus('database'))
            ->withUnavailable()
            ->withUnavailableReason('connection failure, unknown');

        return $this->createExceptionDataProvider(
            'unknown connection error',
            $expectedStatus,
            DoctrineExceptionFactory::createUnknownConnectionException()
        );
    }

    /**
     * @return array[]
     */
    private function createExceptionDataProvider(
        string $name,
        ComponentStatusInterface $expectedStatus,
        \Exception $exception
    ): array {
        $dataSets = [];

        foreach (DatabaseInspectionHandler::ENTITY_CLASS_NAMES as $entityClassName) {
            $dataSets['unavailable, ' . $name . ' for ' . $entityClassName] = [
                'entityManager' => $this->createEntityManager([
                    $entityClassName => $exception,
                ]),
                'expectedStatus' => $expectedStatus,
            ];
        }

        return $dataSets;
    }

    /**
     * @param \Exception[] $exceptions
     */
    private function createEntityManager(array $exceptions = []): EntityManagerInterface
    {
        $classNames = [
            CreateFailure::class,
            Machine::class,
            MachineProvider::class,
        ];

        $entityManager = \Mockery::mock(EntityManagerInterface::class);

        foreach ($classNames as $className) {
            $entityManager = $this->foo(
                $entityManager,
                $className,
                $exceptions[$className] ?? null
            );
        }

        \assert($entityManager instanceof EntityManagerInterface);

        return $entityManager;
    }

    /**
     * @param class-string $entityClassName
     */
    private function foo(
        MockInterface $mockEntityManager,
        string $entityClassName,
        ?\Exception $exception = null
    ): MockInterface {
        if ($exception instanceof \Exception) {
            $mockEntityManager
                ->shouldReceive('find')
                ->with($entityClassName, DatabaseInspectionHandler::INVALID_MACHINE_ID)
                ->andThrow($exception);
        } else {
            $mockEntityManager
                ->shouldReceive('find')
                ->with($entityClassName, DatabaseInspectionHandler::INVALID_MACHINE_ID);
        }

        return $mockEntityManager;
    }
}
