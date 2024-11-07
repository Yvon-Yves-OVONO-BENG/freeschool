<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241029214149 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE skill ADD sequence_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE skill ADD CONSTRAINT FK_5E3DE47798FB19AE FOREIGN KEY (sequence_id) REFERENCES sequence (id)');
        $this->addSql('CREATE INDEX IDX_5E3DE47798FB19AE ON skill (sequence_id)');
        $this->addSql('ALTER TABLE student ADD qr_code_roll_of_honor VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE skill DROP FOREIGN KEY FK_5E3DE47798FB19AE');
        $this->addSql('DROP INDEX IDX_5E3DE47798FB19AE ON skill');
        $this->addSql('ALTER TABLE skill DROP sequence_id');
        $this->addSql('ALTER TABLE student DROP qr_code_roll_of_honor');
    }
}
