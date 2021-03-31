<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210330152208 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE create_failure (
                id VARCHAR(128) NOT NULL, 
                code INT NOT NULL, 
                reason TEXT NOT NULL, 
                context TEXT,
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('COMMENT ON COLUMN create_failure.context IS \'(DC2Type:simple_array)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE create_failure');
    }
}
