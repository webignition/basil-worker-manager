<?php

namespace App\Services\ServiceStatusInspector;

interface ComponentInspectorInterface
{
    /**
     * @throws \Throwable
     */
    public function __invoke(): void;
}
