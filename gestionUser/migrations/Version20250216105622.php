<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250216105622 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE utilisateur ADD image VARCHAR(255) DEFAULT NULL, ADD diploma VARCHAR(255) DEFAULT NULL, ADD is_verified TINYINT(1) DEFAULT 0 NOT NULL, CHANGE date_naissance date_naissance DATE DEFAULT NULL, CHANGE specialite specialite VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE utilisateur DROP image, DROP diploma, DROP is_verified, CHANGE date_naissance date_naissance DATE NOT NULL, CHANGE specialite specialite VARCHAR(255) NOT NULL');
    }
}
