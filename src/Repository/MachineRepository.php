<?php

namespace App\Repository;

use App\Entity\Machine;
use App\Model\MachineInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MachineInterface|null find($id, $lockMode = null, $lockVersion = null)
 * @method MachineInterface|null findOneBy(array $criteria, array $orderBy = null)
 * @method MachineInterface[]    findAll()
 * @method MachineInterface[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MachineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Machine::class);
    }
}
