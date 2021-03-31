<?php

namespace App\Entity;

use App\Repository\CreateFailureRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CreateFailureRepository::class)
 */
class CreateFailure
{
    public const CODE_UNKNOWN = 0;
    public const REASON_UNKNOWN = 'unknown';

    public const CODE_UNSUPPORTED_PROVIDER = 1;
    public const REASON_UNSUPPORTED_PROVIDER = 'unsupported provider';

    public const CODE_API_LIMIT_EXCEEDED = 2;
    public const REASON_API_LIMIT_EXCEEDED = 'api limit exceeded';

    public const CODE_API_AUTHENTICATION_FAILURE = 3;
    public const REASON_API_AUTHENTICATION_FAILURE = 'api authentication failure';

    public const CODE_CURL_ERROR = 4;
    public const REASON_CURL_ERROR = 'http transport error';

    public const CODE_HTTP_ERROR = 5;
    public const REASON_HTTP_ERROR = 'http application error';

    public const CODE_UNPROCESSABLE_REQUEST = 6;
    public const REASON_UNPROCESSABLE_REQUEST = 'unprocessable request';

    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=128)
     */
    private string $id;

    /**
     * @ORM\Column(type="integer")
     *
     * @var self::CODE_*
     */
    private int $code;

    /**
     * @ORM\Column(type="text")
     *
     * @var self::REASON_*
     */
    private string $reason;

    /**
     * @ORM\Column(type="simple_array")
     *
     * @var array<string, int|string>
     */
    private array $context = [];

    /**
     * @param self::CODE_* $code
     * @param self::REASON_* $reason
     * @param array<string, int|string> $context
     */
    public static function create(string $machineId, int $code, string $reason, array $context = []): self
    {
        $entity = new CreateFailure();
        $entity->id = $machineId;
        $entity->code = $code;
        $entity->reason = $reason;
        $entity->context = $context;

        return $entity;
    }
}
