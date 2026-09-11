<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260724083701 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ai_query_history (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, question LONGTEXT NOT NULL, answer LONGTEXT DEFAULT NULL, status VARCHAR(40) NOT NULL, output_format VARCHAR(30) NOT NULL, query_plan LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', result_preview LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\', row_count INT DEFAULT NULL, file_path VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_5C257D4FB03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE time_table_slot_template (id INT AUTO_INCREMENT NOT NULL, school_year_id INT DEFAULT NULL, sub_system_id INT DEFAULT NULL, row_order INT NOT NULL, type VARCHAR(20) NOT NULL, start_time VARCHAR(8) NOT NULL, end_time VARCHAR(8) NOT NULL, label VARCHAR(100) DEFAULT NULL, active TINYINT(1) NOT NULL, slug VARCHAR(255) DEFAULT NULL, INDEX IDX_EF11907FD2EECC3F (school_year_id), INDEX IDX_EF11907FC298691B (sub_system_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ai_query_history ADD CONSTRAINT FK_5C257D4FB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE time_table_slot_template ADD CONSTRAINT FK_EF11907FD2EECC3F FOREIGN KEY (school_year_id) REFERENCES school_year (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE time_table_slot_template ADD CONSTRAINT FK_EF11907FC298691B FOREIGN KEY (sub_system_id) REFERENCES sub_system (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE absence_teacher DROP FOREIGN KEY FK_7970835D896DBBDE');
        $this->addSql('DROP INDEX IDX_7970835D896DBBDE ON absence_teacher');
        $this->addSql('ALTER TABLE absence_teacher ADD supprime TINYINT(1) NOT NULL, CHANGE updated_by_id update_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE absence_teacher ADD CONSTRAINT FK_7970835DCA83C286 FOREIGN KEY (update_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_7970835DCA83C286 ON absence_teacher (update_by_id)');
        $this->addSql('ALTER TABLE time_table CHANGE start_time start_time VARCHAR(8) DEFAULT NULL, CHANGE end_time end_time VARCHAR(8) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD email VARCHAR(180) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE ai_query_history DROP FOREIGN KEY FK_5C257D4FB03A8386');
        $this->addSql('ALTER TABLE time_table_slot_template DROP FOREIGN KEY FK_EF11907FD2EECC3F');
        $this->addSql('ALTER TABLE time_table_slot_template DROP FOREIGN KEY FK_EF11907FC298691B');
        $this->addSql('DROP TABLE ai_query_history');
        $this->addSql('DROP TABLE time_table_slot_template');
        $this->addSql('ALTER TABLE absence_teacher DROP FOREIGN KEY FK_7970835DCA83C286');
        $this->addSql('DROP INDEX IDX_7970835DCA83C286 ON absence_teacher');
        $this->addSql('ALTER TABLE absence_teacher DROP supprime, CHANGE update_by_id updated_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE absence_teacher ADD CONSTRAINT FK_7970835D896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_7970835D896DBBDE ON absence_teacher (updated_by_id)');
        $this->addSql('ALTER TABLE time_table CHANGE start_time start_time VARCHAR(255) NOT NULL, CHANGE end_time end_time VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE user DROP email');
    }
}
