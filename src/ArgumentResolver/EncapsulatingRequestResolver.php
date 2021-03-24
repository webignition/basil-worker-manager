<?php

declare(strict_types=1);

namespace App\ArgumentResolver;

use App\Request\EncapsulatingRequestInterface;
use App\Request\MachineCreateRequest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ArgumentValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class EncapsulatingRequestResolver implements ArgumentValueResolverInterface
{
    private const SUPPORTED_CLASSES = [
        MachineCreateRequest::class,
    ];

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return in_array($argument->getType(), self::SUPPORTED_CLASSES);
    }

    /**
     * @param Request $request
     * @param ArgumentMetadata $argument
     *
     * @return \Generator<EncapsulatingRequestInterface>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): \Generator
    {
        yield new MachineCreateRequest($request);
    }
}
