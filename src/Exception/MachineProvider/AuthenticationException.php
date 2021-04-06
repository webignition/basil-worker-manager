<?php

namespace App\Exception\MachineProvider;

use webignition\BasilWorkerManagerInterfaces\Exception\MachineProvider\AuthenticationExceptionInterface;

class AuthenticationException extends Exception implements AuthenticationExceptionInterface
{
}
