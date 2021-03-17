<?php

declare(strict_types=1);

namespace App\Tests\Unit\Model\Worker;

use App\Model\Worker\State;
use App\Model\Worker\StateTransitionSequence;
use PHPUnit\Framework\TestCase;

class StateTransitionSequenceTest extends TestCase
{
    /**
     * @dataProvider containsDataProvider
     *
     * @param State::VALUE_* $state
     */
    public function testContains(StateTransitionSequence $sequence, string $state, bool $expectedContains): void
    {
        self::assertSame($expectedContains, $sequence->contains($state));
    }

    /**
     * @return array[]
     */
    public function containsDataProvider(): array
    {
        return [
            'empty' => [
                'sequence' => new StateTransitionSequence([]),
                'state' => State::VALUE_CREATE_RECEIVED,
                'expectedContains' => false,
            ],
            'does not contain, not present' => [
                'sequence' => new StateTransitionSequence([
                    State::VALUE_CREATE_RECEIVED,
                    State::VALUE_CREATE_REQUESTED,
                    State::VALUE_CREATE_FAILED,
                ]),
                'state' => State::VALUE_UP_STARTED,
                'expectedContains' => false,
            ],
            'does contain, first item' => [
                'sequence' => new StateTransitionSequence([
                    State::VALUE_CREATE_RECEIVED,
                    State::VALUE_CREATE_REQUESTED,
                    State::VALUE_UP_STARTED,
                ]),
                'state' => State::VALUE_CREATE_RECEIVED,
                'expectedContains' => true,
            ],
            'does contain, intermediary item' => [
                'sequence' => new StateTransitionSequence([
                    State::VALUE_CREATE_RECEIVED,
                    State::VALUE_CREATE_REQUESTED,
                    State::VALUE_UP_STARTED,
                ]),
                'state' => State::VALUE_CREATE_REQUESTED,
                'expectedContains' => true,
            ],
            'does contain, last item' => [
                'sequence' => new StateTransitionSequence([
                    State::VALUE_CREATE_RECEIVED,
                    State::VALUE_CREATE_REQUESTED,
                    State::VALUE_UP_STARTED,
                ]),
                'state' => State::VALUE_UP_STARTED,
                'expectedContains' => true,
            ],
        ];
    }

    /**
     * @dataProvider endsWithDataProvider
     *
     * @param State::VALUE_* $state
     */
    public function testEndsWith(StateTransitionSequence $sequence, string $state, bool $expectedEndsWith): void
    {
        self::assertSame($expectedEndsWith, $sequence->endsWith($state));
    }

    /**
     * @return array[]
     */
    public function endsWithDataProvider(): array
    {
        return [
            'empty' => [
                'sequence' => new StateTransitionSequence([]),
                'state' => State::VALUE_CREATE_RECEIVED,
                'expectedEndsWith' => false,
            ],
            'does not end with, not present' => [
                'sequence' => new StateTransitionSequence([
                    State::VALUE_CREATE_RECEIVED,
                    State::VALUE_CREATE_REQUESTED,
                    State::VALUE_CREATE_FAILED,
                ]),
                'state' => State::VALUE_UP_STARTED,
                'expectedEndsWith' => false,
            ],
            'does not end with, first item' => [
                'sequence' => new StateTransitionSequence([
                    State::VALUE_CREATE_RECEIVED,
                    State::VALUE_CREATE_REQUESTED,
                    State::VALUE_UP_STARTED,
                ]),
                'state' => State::VALUE_CREATE_RECEIVED,
                'expectedEndsWith' => false,
            ],
            'does not end with, intermediary item' => [
                'sequence' => new StateTransitionSequence([
                    State::VALUE_CREATE_RECEIVED,
                    State::VALUE_CREATE_REQUESTED,
                    State::VALUE_UP_STARTED,
                ]),
                'state' => State::VALUE_CREATE_REQUESTED,
                'expectedEndsWith' => false,
            ],
            'does end with, last item' => [
                'sequence' => new StateTransitionSequence([
                    State::VALUE_CREATE_RECEIVED,
                    State::VALUE_CREATE_REQUESTED,
                    State::VALUE_UP_STARTED,
                ]),
                'state' => State::VALUE_UP_STARTED,
                'expectedEndsWith' => true,
            ],
        ];
    }

    /**
     * @dataProvider sliceReturnsStateTransitionsDataProvider
     *
     * @param State::VALUE_* $start
     * @param State::VALUE_* $end
     */
    public function testSliceReturnsStateTransitions(
        StateTransitionSequence $sequence,
        string $start,
        string $end,
        StateTransitionSequence $expectedTransitions
    ): void {
        self::assertEquals($expectedTransitions, $sequence->slice($start, $end));
    }

    /**
     * @return array[]
     */
    public function sliceReturnsStateTransitionsDataProvider(): array
    {
        return [
            'start is first, end is last' => [
                'sequence' => new StateTransitionSequence([
                    State::VALUE_CREATE_RECEIVED,
                    State::VALUE_CREATE_REQUESTED,
                    State::VALUE_UP_STARTED,
                ]),
                'start' => State::VALUE_CREATE_RECEIVED,
                'end' => State::VALUE_UP_STARTED,
                'expectedTransitions' => new StateTransitionSequence([
                    State::VALUE_CREATE_RECEIVED,
                    State::VALUE_CREATE_REQUESTED,
                    State::VALUE_UP_STARTED,
                ]),
            ],
            'contains only start and end' => [
                'sequence' => new StateTransitionSequence([
                    State::VALUE_CREATE_RECEIVED,
                    State::VALUE_UP_STARTED,
                ]),
                'start' => State::VALUE_CREATE_RECEIVED,
                'end' => State::VALUE_UP_STARTED,
                'expectedTransitions' => new StateTransitionSequence([
                    State::VALUE_CREATE_RECEIVED,
                    State::VALUE_UP_STARTED,
                ]),
            ],
            'single state' => [
                'sequence' => new StateTransitionSequence([
                    State::VALUE_CREATE_RECEIVED,
                ]),
                'start' => State::VALUE_CREATE_RECEIVED,
                'end' => State::VALUE_CREATE_RECEIVED,
                'expectedTransitions' => new StateTransitionSequence([
                    State::VALUE_CREATE_RECEIVED,
                ]),
            ],
            'inner subset' => [
                'sequence' => new StateTransitionSequence([
                    State::VALUE_CREATE_RECEIVED,
                    State::VALUE_CREATE_REQUESTED,
                    State::VALUE_UP_STARTED,
                    State::VALUE_UP_ACTIVE,
                    State::VALUE_DELETE_RECEIVED,
                    State::VALUE_DELETE_REQUESTED,
                    State::VALUE_DELETE_DELETED,
                ]),
                'start' => State::VALUE_UP_STARTED,
                'end' => State::VALUE_DELETE_REQUESTED,
                'expectedTransitions' => new StateTransitionSequence([
                    State::VALUE_UP_STARTED,
                    State::VALUE_UP_ACTIVE,
                    State::VALUE_DELETE_RECEIVED,
                    State::VALUE_DELETE_REQUESTED,
                ]),
            ],
        ];
    }

    /**
     * @dataProvider sliceReturnsNullDataProvider
     *
     * @param State::VALUE_* $start
     * @param State::VALUE_* $end
     */
    public function testSliceReturnsNull(
        StateTransitionSequence $sequence,
        string $start,
        string $end
    ): void {
        self::assertNull($sequence->slice($start, $end));
    }

    /**
     * @return array[]
     */
    public function sliceReturnsNullDataProvider(): array
    {
        return [
            'start does not exist' => [
                'sequence' => new StateTransitionSequence([
                    State::VALUE_CREATE_RECEIVED,
                    State::VALUE_CREATE_REQUESTED,
                    State::VALUE_UP_STARTED,
                ]),
                'start' => State::VALUE_DELETE_DELETED,
                'end' => State::VALUE_UP_STARTED,
            ],
            'end does not exist' => [
                'sequence' => new StateTransitionSequence([
                    State::VALUE_CREATE_RECEIVED,
                    State::VALUE_CREATE_REQUESTED,
                    State::VALUE_UP_STARTED,
                ]),
                'start' => State::VALUE_CREATE_RECEIVED,
                'end' => State::VALUE_UP_ACTIVE,
            ],
            'end comes before start' => [
                'sequence' => new StateTransitionSequence([
                    State::VALUE_CREATE_RECEIVED,
                    State::VALUE_CREATE_REQUESTED,
                    State::VALUE_UP_STARTED,
                ]),
                'start' => State::VALUE_CREATE_REQUESTED,
                'end' => State::VALUE_CREATE_RECEIVED,
            ],
        ];
    }

    /**
     * @dataProvider containsWithinDataProvider
     *
     * @param State::VALUE_* $state
     */
    public function testContainsWithin(StateTransitionSequence $sequence, string $state, bool $expectedContains): void
    {
        self::assertSame($expectedContains, $sequence->containsWithin($state));
    }

    /**
     * @return array[]
     */
    public function containsWithinDataProvider(): array
    {
        return [
            'empty' => [
                'sequence' => new StateTransitionSequence([]),
                'state' => State::VALUE_CREATE_RECEIVED,
                'expectedContains' => false,
            ],
            'does not contain, not present' => [
                'sequence' => new StateTransitionSequence([
                    State::VALUE_CREATE_RECEIVED,
                    State::VALUE_CREATE_REQUESTED,
                    State::VALUE_CREATE_FAILED,
                ]),
                'state' => State::VALUE_UP_STARTED,
                'expectedContains' => false,
            ],
            'does contain, first item' => [
                'sequence' => new StateTransitionSequence([
                    State::VALUE_CREATE_RECEIVED,
                    State::VALUE_CREATE_REQUESTED,
                    State::VALUE_UP_STARTED,
                ]),
                'state' => State::VALUE_CREATE_RECEIVED,
                'expectedContains' => true,
            ],
            'does contain, intermediary item' => [
                'sequence' => new StateTransitionSequence([
                    State::VALUE_CREATE_RECEIVED,
                    State::VALUE_CREATE_REQUESTED,
                    State::VALUE_UP_STARTED,
                ]),
                'state' => State::VALUE_CREATE_REQUESTED,
                'expectedContains' => true,
            ],
            'does not contain, last item' => [
                'sequence' => new StateTransitionSequence([
                    State::VALUE_CREATE_RECEIVED,
                    State::VALUE_CREATE_REQUESTED,
                    State::VALUE_UP_STARTED,
                ]),
                'state' => State::VALUE_UP_STARTED,
                'expectedContains' => false,
            ],
        ];
    }

    /**
     * @dataProvider sliceEndingWithReturnsStateTransitionsDataProvider
     *
     * @param State::VALUE_* $end
     */
    public function testSliceEndingWithReturnsStateTransitions(
        StateTransitionSequence $sequence,
        string $end,
        StateTransitionSequence $expectedTransitions
    ): void {
        self::assertEquals($expectedTransitions, $sequence->sliceEndingWith($end));
    }

    /**
     * @return array[]
     */
    public function sliceEndingWithReturnsStateTransitionsDataProvider(): array
    {
        return [
            'end is last' => [
                'sequence' => new StateTransitionSequence([
                    State::VALUE_CREATE_RECEIVED,
                    State::VALUE_CREATE_REQUESTED,
                    State::VALUE_UP_STARTED,
                ]),
                'end' => State::VALUE_UP_STARTED,
                'expectedTransitions' => new StateTransitionSequence([
                    State::VALUE_CREATE_RECEIVED,
                    State::VALUE_CREATE_REQUESTED,
                    State::VALUE_UP_STARTED,
                ]),
            ],
            'single state' => [
                'sequence' => new StateTransitionSequence([
                    State::VALUE_CREATE_RECEIVED,
                ]),
                'end' => State::VALUE_CREATE_RECEIVED,
                'expectedTransitions' => new StateTransitionSequence([
                    State::VALUE_CREATE_RECEIVED,
                ]),
            ],
            'end is intermediate' => [
                'sequence' => new StateTransitionSequence([
                    State::VALUE_CREATE_RECEIVED,
                    State::VALUE_CREATE_REQUESTED,
                    State::VALUE_UP_STARTED,
                    State::VALUE_UP_ACTIVE,
                    State::VALUE_DELETE_RECEIVED,
                    State::VALUE_DELETE_REQUESTED,
                    State::VALUE_DELETE_DELETED,
                ]),
                'end' => State::VALUE_UP_STARTED,
                'expectedTransitions' => new StateTransitionSequence([
                    State::VALUE_CREATE_RECEIVED,
                    State::VALUE_CREATE_REQUESTED,
                    State::VALUE_UP_STARTED,
                ]),
            ],
        ];
    }

    /**
     * @dataProvider sliceEndingWithReturnsNullDataProvider
     *
     * @param State::VALUE_* $end
     */
    public function testSliceEndingWithReturnsNull(StateTransitionSequence $sequence, string $end): void
    {
        self::assertNull($sequence->sliceEndingWith($end));
    }

    /**
     * @return array[]
     */
    public function sliceEndingWithReturnsNullDataProvider(): array
    {
        return [
            'end does not exist' => [
                'sequence' => new StateTransitionSequence([
                    State::VALUE_CREATE_RECEIVED,
                ]),
                'end' => State::VALUE_UP_ACTIVE,
            ],
        ];
    }
}
