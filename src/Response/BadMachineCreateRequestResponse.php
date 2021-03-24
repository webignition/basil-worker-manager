<?php

declare(strict_types=1);

namespace App\Response;

class BadMachineCreateRequestResponse extends ErrorResponse
{
    private const TYPE = 'machine-create-request';

    private const CODE_ID_MISSING = 100;
    private const CODE_ID_TAKEN = 200;
    private const MESSAGE_ID_MISSING = 'id missing';
    private const MESSAGE_ID_TAKEN = 'id taken';

    public function __construct(string $message, int $code, int $status = self::HTTP_BAD_REQUEST)
    {
        parent::__construct(self::TYPE, $message, $code, $status);
    }

    public static function createIdMissingResponse(): self
    {
        return new BadMachineCreateRequestResponse(
            self::MESSAGE_ID_MISSING,
            self::CODE_ID_MISSING
        );
    }

    public static function createIdTakenResponse(): self
    {
        return new BadMachineCreateRequestResponse(
            self::MESSAGE_ID_TAKEN,
            self::CODE_ID_TAKEN
        );
    }
}
