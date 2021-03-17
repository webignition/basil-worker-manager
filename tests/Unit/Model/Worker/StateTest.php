<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model\Worker;

use App\Model\Worker\State;
use PHPUnit\Framework\TestCase;

class StateTest extends TestCase
{
    public function testToString(): void
    {
        $state = new State(State::VALUE_CREATE_REQUESTED);

        self::assertSame(State::VALUE_CREATE_REQUESTED, (string) $state);
    }

    public function testIs(): void
    {
        self::assertTrue(State::is(State::VALUE_CREATE_RECEIVED));
        self::assertTrue(State::is(State::VALUE_CREATE_REQUESTED));
        self::assertTrue(State::is(State::VALUE_CREATE_FAILED));
        self::assertTrue(State::is(State::VALUE_UP_STARTED));
        self::assertTrue(State::is(State::VALUE_UP_ACTIVE));
        self::assertTrue(State::is(State::VALUE_DELETE_RECEIVED));
        self::assertTrue(State::is(State::VALUE_DELETE_REQUESTED));
        self::assertTrue(State::is(State::VALUE_DELETE_FAILED));
        self::assertTrue(State::is(State::VALUE_DELETE_DELETED));

        self::assertFalse(State::is('invalid'));
    }
}
