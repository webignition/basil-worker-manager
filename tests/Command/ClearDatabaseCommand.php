<?php

namespace App\Tests\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use webignition\BasilWorkerManager\PersistenceBundle\Entity\Machine;
use webignition\BasilWorkerManager\PersistenceBundle\Entity\MachineProvider;

class ClearDatabaseCommand extends Command
{
    protected static $defaultName = 'app:test:clear-database';

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct(null);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->removeAllEntities(Machine::class);
        $this->removeAllEntities(MachineProvider::class);

        return Command::SUCCESS;
    }

    /**
     * @param class-string $entityClassName
     */
    private function removeAllEntities(string $entityClassName): void
    {
        $repository = $this->entityManager->getRepository($entityClassName);
        if ($repository instanceof ObjectRepository) {
            $entities = $repository->findAll();

            foreach ($entities as $entity) {
                $this->entityManager->remove($entity);
                $this->entityManager->flush();
            }
        }
    }
}
