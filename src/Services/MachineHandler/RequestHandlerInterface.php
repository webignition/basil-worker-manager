<?php

declare(strict_types=1);

namespace App\Services\MachineHandler;

use App\Model\ApiRequest\MachineRequestInterface;
use App\Model\ApiRequestOutcome;
use App\Model\MachineProviderActionInterface;

interface RequestHandlerInterface
{
    /**
     * @param MachineProviderActionInterface::ACTION_* $type
     */
    public function handles(string $type): bool;
    public function handle(MachineRequestInterface $request): ApiRequestOutcome;
}
