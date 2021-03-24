<?php

declare(strict_types=1);

namespace App\Services\MachineHandler;

use App\Model\ApiRequestOutcome;
use App\Model\MachineProviderActionInterface;
use App\Model\MachineRequestInterface;

interface RequestHandlerInterface
{
    /**
     * @param MachineProviderActionInterface::ACTION_* $type
     */
    public function handles(string $type): bool;
    public function handle(MachineRequestInterface $request): ApiRequestOutcome;
}
