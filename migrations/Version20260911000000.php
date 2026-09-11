<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260911000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Ajoute le droit permettant au proviseur de bloquer l'ajout et la modification des élèves par administrateur.";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD student_management_blocked TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP student_management_blocked');
    }
}
