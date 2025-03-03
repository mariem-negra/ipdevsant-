<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250225140858 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event CHANGE titre titre VARCHAR(255) NOT NULL, CHANGE lieu lieu VARCHAR(255) NOT NULL, CHANGE discription discription VARCHAR(255) NOT NULL, CHANGE nbplace nbplace INT NOT NULL');
        $this->addSql('ALTER TABLE reservation CHANGE nomreserv nomreserv VARCHAR(255) NOT NULL, CHANGE mail mail VARCHAR(255) NOT NULL, CHANGE nbrpersonne nbrpersonne INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event CHANGE titre titre VARCHAR(255) DEFAULT NULL, CHANGE lieu lieu VARCHAR(255) DEFAULT NULL, CHANGE discription discription VARCHAR(255) DEFAULT NULL, CHANGE nbplace nbplace INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation CHANGE nomreserv nomreserv VARCHAR(255) DEFAULT NULL, CHANGE mail mail VARCHAR(255) DEFAULT NULL, CHANGE nbrpersonne nbrpersonne INT DEFAULT NULL');
    }
}
