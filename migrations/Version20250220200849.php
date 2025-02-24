<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250220200849 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE suivie_medical ADD id_historique_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE suivie_medical ADD CONSTRAINT FK_1EB53CF8DF00A23B FOREIGN KEY (id_historique_id) REFERENCES historique_traitement (id)');
        $this->addSql('CREATE INDEX IDX_1EB53CF8DF00A23B ON suivie_medical (id_historique_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE suivie_medical DROP FOREIGN KEY FK_1EB53CF8DF00A23B');
        $this->addSql('DROP INDEX IDX_1EB53CF8DF00A23B ON suivie_medical');
        $this->addSql('ALTER TABLE suivie_medical DROP id_historique_id');
    }
}
