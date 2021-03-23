<?php

namespace App\Services;

use App\Model\Worker\StateTransitionSequence;

class MachineStateTransitionSequences
{
    /**
     * @var StateTransitionSequence[]
     */
    private array $sequences;

    /**
     * @param StateTransitionSequence[] $sequences
     */
    public function __construct(
        array $sequences
    ) {
        $this->sequences = array_filter($sequences, function ($value) {
            return $value instanceof StateTransitionSequence;
        });
    }

    /**
     * @return StateTransitionSequence[]
     */
    public function getSequences(): array
    {
        return $this->sequences;
    }
}
