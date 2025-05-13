<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250409175446 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE skill CHANGE skill skill VARCHAR(47) NOT NULL');
        $this->addSql('ALTER TABLE student ADD profession_tuteur VARCHAR(255) DEFAULT NULL, ADD email_parent VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD slug VARCHAR(255) DEFAULT NULL, ADD bloque TINYINT(1) DEFAULT NULL, ADD supprime TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE skill CHANGE skill skill VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE student DROP profession_tuteur, DROP email_parent');
        $this->addSql('ALTER TABLE user DROP slug, DROP bloque, DROP supprime');
    }
}
