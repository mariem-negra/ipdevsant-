<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250216110357 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE demande (id INT AUTO_INCREMENT NOT NULL, patient_id INT NOT NULL, date DATETIME NOT NULL, eau DOUBLE PRECISION NOT NULL, nbr_repas INT NOT NULL, snacks TINYINT(1) NOT NULL, calories DOUBLE PRECISION DEFAULT NULL, activity VARCHAR(255) NOT NULL, sommeil VARCHAR(255) DEFAULT NULL, duree_activite DOUBLE PRECISION NOT NULL, INDEX IDX_2694D7A56B899279 (patient_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE recommandation (id INT AUTO_INCREMENT NOT NULL, demande_id INT NOT NULL, petit_dejeuner VARCHAR(255) NOT NULL, dejeuner VARCHAR(255) NOT NULL, diner VARCHAR(255) NOT NULL, activity VARCHAR(255) NOT NULL, calories DOUBLE PRECISION NOT NULL, duree DOUBLE PRECISION NOT NULL, supplements VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_C7782A2880E95E18 (demande_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, mot_de_passe VARCHAR(255) NOT NULL, role VARCHAR(255) NOT NULL, date_naissance DATE DEFAULT NULL, specialite VARCHAR(255) DEFAULT NULL, telephone INT NOT NULL, image VARCHAR(255) DEFAULT NULL, diploma VARCHAR(255) DEFAULT NULL, is_verified TINYINT(1) DEFAULT 0 NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE demande ADD CONSTRAINT FK_2694D7A56B899279 FOREIGN KEY (patient_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE recommandation ADD CONSTRAINT FK_C7782A2880E95E18 FOREIGN KEY (demande_id) REFERENCES demande (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE demande DROP FOREIGN KEY FK_2694D7A56B899279');
        $this->addSql('ALTER TABLE recommandation DROP FOREIGN KEY FK_C7782A2880E95E18');
        $this->addSql('DROP TABLE demande');
        $this->addSql('DROP TABLE recommandation');
        $this->addSql('DROP TABLE utilisateur');
    }
}
