<?php

declare(strict_types=1);

namespace App\MessageDispatcher;

use App\Message\MachineRequestInterface;
use App\Message\RetryableRequestInterface;
use App\Model\MachineRequestDispatcherConfiguration as DispatcherConfiguration;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;

class MachineRequestMessageDispatcher
{
    /**
     * @var DispatcherConfiguration[]
     */
    private array $configurations;

    /**
     * @param DispatcherConfiguration[] $configurations
     */
    public function __construct(
        private MessageBusInterface $messageBus,
        array $configurations,
    ) {
        foreach ($configurations as $type => $configuration) {
            if ($configuration instanceof DispatcherConfiguration) {
                $this->configurations[$type] = $configuration;
            }
        }
    }

    public function dispatch(MachineRequestInterface $message): void
    {
        $configuration = $this->configurations[$message::class] ?? new DispatcherConfiguration();
        $stamps = [];
        $dispatchDelay = $this->getDispatchDelay($configuration, $message);

        if ($dispatchDelay > 0) {
            $stamps = [
                new DelayStamp($dispatchDelay * 1000)
            ];
        }

        $this->messageBus->dispatch(new Envelope($message, $stamps));
    }

    private function getDispatchDelay(DispatcherConfiguration $configuration, MachineRequestInterface $request): int
    {
        $retryCount = $request instanceof RetryableRequestInterface
            ? $request->getRetryCount()
            : 0;

        if (0 === $retryCount) {
            return $this->getInitialDispatchDelay($configuration);
        }

        return $configuration->getDispatchDelayInSeconds();
    }

    private function getInitialDispatchDelay(DispatcherConfiguration $configuration): int
    {
        $initialDispatchDelayInSeconds = $configuration->getInitialDispatchDelayInSeconds();

        return is_int($initialDispatchDelayInSeconds)
            ? $initialDispatchDelayInSeconds
            : $configuration->getDispatchDelayInSeconds();
    }
}
