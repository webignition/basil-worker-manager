<?php

declare(strict_types=1);

namespace App\Tests\Unit\ArgumentResolver;

use App\ArgumentResolver\EncapsulatingRequestResolver;
use App\Request\EncapsulatingRequestInterface;
use App\Request\WorkerCreateRequest;
use App\Tests\Mock\MockArgumentMetadata;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class EncapsulatingRequestResolverTest extends TestCase
{
    private EncapsulatingRequestResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new EncapsulatingRequestResolver();
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSupports(ArgumentMetadata $argumentMetadata, bool $expectedSupports): void
    {
        $request = \Mockery::mock(Request::class);

        self::assertSame($expectedSupports, $this->resolver->supports($request, $argumentMetadata));
    }

    /**
     * @return array[]
     */
    public function supportsDataProvider(): array
    {
        return [
            'does support' => [
                'argumentMetadata' => (new MockArgumentMetadata())
                    ->withGetTypeCall(WorkerCreateRequest::class)
                    ->getMock(),
                'expectedSupports' => true,
            ],
            'does not support' => [
                'argumentMetadata' => (new MockArgumentMetadata())
                    ->withGetTypeCall('string')
                    ->getMock(),
                'expectedSupports' => false,
            ],
        ];
    }

    /**
     * @dataProvider resolveWorkerCreateRequestDataProvider
     */
    public function testResolve(
        Request $request,
        ArgumentMetadata $argumentMetadata,
        EncapsulatingRequestInterface $expectedEncapsulatingRequest
    ): void {
        $generator = $this->resolver->resolve($request, $argumentMetadata);
        $encapsulatingRequest = $generator->current();

        self::assertEquals($expectedEncapsulatingRequest, $encapsulatingRequest);
    }

    /**
     * @return array[]
     */
    public function resolveWorkerCreateRequestDataProvider(): array
    {
        $label = md5('label content');

        $argumentMetadata = (new MockArgumentMetadata())
            ->withGetTypeCall(WorkerCreateRequest::class)
            ->getMock();

        return [
            'WorkerCreateRequest: empty' => [
                'request' => new Request(),
                'argumentMetadata' => $argumentMetadata,
                'expectedEncapsulatingRequest' => new WorkerCreateRequest(new Request()),
            ],
            'JobCreateRequest: label present' => [
                'request' => new Request([], [
                    WorkerCreateRequest::KEY_LABEL => $label,
                ]),
                'argumentMetadata' => $argumentMetadata,
                'expectedEncapsulatingRequest' => new WorkerCreateRequest(new Request([], [
                    WorkerCreateRequest::KEY_LABEL => $label,
                ])),
            ],
        ];
    }
}
