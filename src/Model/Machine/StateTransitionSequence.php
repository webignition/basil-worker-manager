<?php

namespace App\Model\Machine;

class StateTransitionSequence
{
    /**
     * @param array<State::VALUE_*> $states
     */
    public function __construct(
        private array $states
    ) {
    }

    /**
     * @param State::VALUE_* $state
     */
    public function contains(string $state): bool
    {
        return false !== array_search($state, $this->states);
    }

    /**
     * @param State::VALUE_* $state
     */
    public function endsWith(string $state): bool
    {
        $states = $this->states;

        return $state === array_pop($states);
    }

    /**
     * @param State::VALUE_* $state
     */
    public function containsWithin(string $state): bool
    {
        return true === $this->contains($state) && false === $this->endsWith($state);
    }

    /**
     * @param State::VALUE_* $start
     * @param State::VALUE_* $end
     */
    public function slice(string $start, string $end): ?self
    {
        $startPosition = array_search($start, $this->states, true);
        if (!is_int($startPosition)) {
            return null;
        }

        $endPosition = array_search($end, $this->states, true);
        if (!is_int($endPosition)) {
            return null;
        }

        if ($startPosition > $endPosition) {
            return null;
        }

        return new StateTransitionSequence(array_slice(
            $this->states,
            $startPosition,
            $endPosition - $startPosition + 1
        ));
    }

    /**
     * @param State::VALUE_* $end
     */
    public function sliceEndingWith(string $end): ?self
    {
        $endPosition = array_search($end, $this->states, true);
        if (!is_int($endPosition)) {
            return null;
        }

        return new StateTransitionSequence(array_slice(
            $this->states,
            0,
            $endPosition + 1
        ));
    }
}
