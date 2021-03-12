<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210312123145 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE worker (
                id UUID NOT NULL, 
                remote_id INT DEFAULT NULL, 
                label VARCHAR(32) NOT NULL, 
                state VARCHAR(255) NOT NULL, 
                provider VARCHAR(255) NOT NULL, 
                ip_addresses TEXT DEFAULT NULL, PRIMARY KEY(id)
            )
        ');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9FB2BF62EA750E8 ON worker (label)');
        $this->addSql('COMMENT ON COLUMN worker.id IS \'(DC2Type:ulid)\'');
        $this->addSql('COMMENT ON COLUMN worker.ip_addresses IS \'(DC2Type:simple_array)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE worker');
    }
}
