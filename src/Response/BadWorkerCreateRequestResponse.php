<?php

declare(strict_types=1);

namespace App\Response;

class BadWorkerCreateRequestResponse extends ErrorResponse
{
    private const TYPE = 'worker-create-request';

    private const CODE_LABEL_MISSING = 100;
    private const CODE_LABEL_TAKEN = 200;
    private const MESSAGE_LABEL_MISSING = 'label missing';
    private const MESSAGE_LABEL_TAKEN = 'label taken';

    public function __construct(string $message, int $code, int $status = self::HTTP_BAD_REQUEST)
    {
        parent::__construct(self::TYPE, $message, $code, $status);
    }

    public static function createLabelMissingResponse(): self
    {
        return new BadWorkerCreateRequestResponse(
            self::MESSAGE_LABEL_MISSING,
            self::CODE_LABEL_MISSING
        );
    }

    public static function createLabelTakenResponse(): self
    {
        return new BadWorkerCreateRequestResponse(
            self::MESSAGE_LABEL_TAKEN,
            self::CODE_LABEL_TAKEN
        );
    }
}
