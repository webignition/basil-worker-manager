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
    public static function create(Machine $machine, int $code, string $reason, array $context = []): self
    {
        $entity = new CreateFailure();
        $entity->id = $machine->getId();
        $entity->code = $code;
        $entity->reason = $reason;
        $entity->context = $context;

        return $entity;
    }
}
