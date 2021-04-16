<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\MachineActionPropertiesInterface;

interface HasSelfPropertiesInterface
{
    public function getSelfProperties(): MachineActionPropertiesInterface;
}
