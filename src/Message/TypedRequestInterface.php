<?php

declare(strict_types=1);

namespace App\Message;

interface TypedRequestInterface
{
    public function getType(): string;
}
