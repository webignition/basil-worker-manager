<?php

declare(strict_types=1);

namespace App\Tests\Functional\Services;

use App\Model\Worker\StateTransitionSequence;
use App\Services\MachineStateTransitionSequences;
use App\Tests\AbstractBaseFunctionalTest;

class WorkerStateTransitionSequencesTest extends AbstractBaseFunctionalTest
{
    private MachineStateTransitionSequences $sequences;

    protected function setUp(): void
    {
        parent::setUp();

        $sequences = self::$container->get(MachineStateTransitionSequences::class);
        if ($sequences instanceof MachineStateTransitionSequences) {
            $this->sequences = $sequences;
        }
    }

    public function testGet(): void
    {
        $sequences = $this->sequences->getSequences();

        foreach ($sequences as $key => $sequence) {
            $sequenceService = self::$container->get('app.services.worker_state_transition_sequence.' . $key);
            self::assertInstanceOf(StateTransitionSequence::class, $sequenceService);
            self::assertSame($sequenceService, $sequence);
        }
    }
}
