<?php

namespace App\Repository;

use App\Entity\Machine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use webignition\BasilWorkerManagerInterfaces\MachineInterface;

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
