<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210318103427 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE machine (
                id VARCHAR(64) NOT NULL, 
                remote_id INT DEFAULT NULL, 
                state VARCHAR(255) NOT NULL, 
                provider VARCHAR(255) NOT NULL, 
                ip_addresses TEXT DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('COMMENT ON COLUMN machine.ip_addresses IS \'(DC2Type:simple_array)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE machine');
    }
}
