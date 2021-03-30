<?php

namespace App\Entity;

use App\Repository\CreateFailureRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CreateFailureRepository::class)
 */
class CreateFailure
{
    /**
     * @ORM\Id
     * @ORM\Column(type="string", length=128)
     */
    private string $id;

    /**
     * @ORM\Column(type="integer")
     */
    private int $code;

    /**
     * @ORM\Column(type="text")
     */
    private string $reason;

    public static function create(Machine $machine, int $code, string $reason): self
    {
        $entity = new CreateFailure();
        $entity->id = $machine->getId();
        $entity->code = $code;
        $entity->reason = $reason;

        return $entity;
    }
}
