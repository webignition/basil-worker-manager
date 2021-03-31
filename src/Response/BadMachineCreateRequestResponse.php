<?php

declare(strict_types=1);

namespace App\Response;

class BadMachineCreateRequestResponse extends ErrorResponse
{
    private const TYPE = 'machine-create-request';

    private const CODE_ID_TAKEN = 100;
    private const MESSAGE_ID_TAKEN = 'id taken';

    public function __construct(string $message, int $code, int $status = self::HTTP_BAD_REQUEST)
    {
        parent::__construct(self::TYPE, $message, $code, $status);
    }

    public static function createIdTakenResponse(): self
    {
        return new BadMachineCreateRequestResponse(
            self::MESSAGE_ID_TAKEN,
            self::CODE_ID_TAKEN
        );
    }
}
