<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Worker;
use App\Model\ProviderInterface;
use PHPUnit\Framework\TestCase;
use webignition\ObjectReflector\ObjectReflector;

class WorkerTest extends TestCase
{
    public function testCreate(): void
    {
        $label = md5('label content');
        $provider = ProviderInterface::NAME_DIGITALOCEAN;

        $worker = Worker::create($label, $provider);

        self::assertSame($label, ObjectReflector::getProperty($worker, 'label'));
        self::assertSame(Worker::STATE_CREATE_RECEIVED, ObjectReflector::getProperty($worker, 'state'));
        self::assertSame($provider, ObjectReflector::getProperty($worker, 'provider'));
        self::assertSame([], ObjectReflector::getProperty($worker, 'ip_addresses'));
    }
}
