<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

use App\Message\MachineRequestInterface;
use App\Model\MachineRequestDispatcherConfiguration;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class MachineRequestMessageDispatcher
{
    /**
     * @var MachineRequestDispatcherConfiguration[]
     */
    private array $configurations;

    /**
     * @param MachineRequestDispatcherConfiguration[] $configurations
     */
    public function __construct(
        private MessageBusInterface $messageBus,
        array $configurations,
    ) {
        foreach ($configurations as $type => $configuration) {
            if ($configuration instanceof MachineRequestDispatcherConfiguration) {
                $this->configurations[$type] = $configuration;
            }
        }
    }

    public function dispatch(MachineRequestInterface $message): void
    {
        $configuration = $this->configurations[$message->getType()] ?? new MachineRequestDispatcherConfiguration();
        if (false === $configuration->isEnabled()) {
            return;
        }

        $stamps = [];
        $dispatchDelay = $this->getDispatchDelay($configuration, $message->getRetryCount());

        if ($dispatchDelay > 0) {
            $stamps = [
                new DelayStamp($dispatchDelay * 1000)
            ];
        }

        $this->messageBus->dispatch(new Envelope($message, $stamps));
    }

    private function getDispatchDelay(MachineRequestDispatcherConfiguration $configuration, int $retryCount): int
    {
        if (0 === $retryCount) {
            return $this->getInitialDispatchDelay($configuration);
        }

        return $configuration->getDispatchDelayInSeconds();
    }

    private function getInitialDispatchDelay(MachineRequestDispatcherConfiguration $configuration): int
    {
        $initialDispatchDelayInSeconds = $configuration->getInitialDispatchDelayInSeconds();

        return is_int($initialDispatchDelayInSeconds)
            ? $initialDispatchDelayInSeconds
            : $configuration->getDispatchDelayInSeconds();
    }
}
