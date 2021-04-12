<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210412093847 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE create_failure (
                id VARCHAR(32) NOT NULL, 
                code INT NOT NULL, 
                reason TEXT NOT NULL, 
                context TEXT DEFAULT NULL, 
                PRIMARY KEY(id)
            )
        ');
        $this->addSql('COMMENT ON COLUMN create_failure.context IS \'(DC2Type:simple_array)\'');

        $this->addSql('
            CREATE TABLE machine (
                id VARCHAR(32) NOT NULL,
                 state VARCHAR(255) NOT NULL, 
                 ip_addresses TEXT DEFAULT NULL,
                  PRIMARY KEY(id)
            )
        ');
        $this->addSql('COMMENT ON COLUMN machine.ip_addresses IS \'(DC2Type:simple_array)\'');

        $this->addSql('
            CREATE TABLE machine_provider (
                id VARCHAR(32) NOT NULL, 
                remote_id INT DEFAULT NULL,
                 provider VARCHAR(255) NOT NULL, 
                 PRIMARY KEY(id)
            )
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE create_failure');
        $this->addSql('DROP TABLE machine');
        $this->addSql('DROP TABLE machine_provider');
    }
}
