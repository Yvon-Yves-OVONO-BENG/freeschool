<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241119161312 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE conseil (id INT AUTO_INCREMENT NOT NULL, term_id INT DEFAULT NULL, student_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, decision VARCHAR(255) DEFAULT NULL, motif VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_3F3F0681E2C35FC (term_id), INDEX IDX_3F3F0681CB944F1A (student_id), INDEX IDX_3F3F0681B03A8386 (created_by_id), INDEX IDX_3F3F0681896DBBDE (updated_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE historique_teacher (id INT AUTO_INCREMENT NOT NULL, teacher_id INT DEFAULT NULL, day_id INT DEFAULT NULL, subject_id INT DEFAULT NULL, sub_system_id INT DEFAULT NULL, classroom_id INT DEFAULT NULL, enregistre_par_id INT DEFAULT NULL, sequence_id INT DEFAULT NULL, heure_debut VARCHAR(255) NOT NULL, heure_fin VARCHAR(255) NOT NULL, nombre_heure NUMERIC(10, 2) DEFAULT NULL, slug VARCHAR(255) NOT NULL, supprime TINYINT(1) NOT NULL, enregistre_le_at DATE NOT NULL, INDEX IDX_D2E2F9AA41807E1D (teacher_id), INDEX IDX_D2E2F9AA9C24126 (day_id), INDEX IDX_D2E2F9AA23EDC87 (subject_id), INDEX IDX_D2E2F9AAC298691B (sub_system_id), INDEX IDX_D2E2F9AA6278D5A8 (classroom_id), INDEX IDX_D2E2F9AACB5FDB3E (enregistre_par_id), INDEX IDX_D2E2F9AA98FB19AE (sequence_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE question_secrete (id INT AUTO_INCREMENT NOT NULL, question_secrete VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reponse_question (id INT AUTO_INCREMENT NOT NULL, question_secrete_id INT DEFAULT NULL, user_id INT DEFAULT NULL, reponse VARCHAR(255) NOT NULL, INDEX IDX_E97BC6396BD4A821 (question_secrete_id), INDEX IDX_E97BC639A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE conseil ADD CONSTRAINT FK_3F3F0681E2C35FC FOREIGN KEY (term_id) REFERENCES term (id)');
        $this->addSql('ALTER TABLE conseil ADD CONSTRAINT FK_3F3F0681CB944F1A FOREIGN KEY (student_id) REFERENCES student (id)');
        $this->addSql('ALTER TABLE conseil ADD CONSTRAINT FK_3F3F0681B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE conseil ADD CONSTRAINT FK_3F3F0681896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE historique_teacher ADD CONSTRAINT FK_D2E2F9AA41807E1D FOREIGN KEY (teacher_id) REFERENCES teacher (id)');
        $this->addSql('ALTER TABLE historique_teacher ADD CONSTRAINT FK_D2E2F9AA9C24126 FOREIGN KEY (day_id) REFERENCES day (id)');
        $this->addSql('ALTER TABLE historique_teacher ADD CONSTRAINT FK_D2E2F9AA23EDC87 FOREIGN KEY (subject_id) REFERENCES subject (id)');
        $this->addSql('ALTER TABLE historique_teacher ADD CONSTRAINT FK_D2E2F9AAC298691B FOREIGN KEY (sub_system_id) REFERENCES sub_system (id)');
        $this->addSql('ALTER TABLE historique_teacher ADD CONSTRAINT FK_D2E2F9AA6278D5A8 FOREIGN KEY (classroom_id) REFERENCES classroom (id)');
        $this->addSql('ALTER TABLE historique_teacher ADD CONSTRAINT FK_D2E2F9AACB5FDB3E FOREIGN KEY (enregistre_par_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE historique_teacher ADD CONSTRAINT FK_D2E2F9AA98FB19AE FOREIGN KEY (sequence_id) REFERENCES sequence (id)');
        $this->addSql('ALTER TABLE reponse_question ADD CONSTRAINT FK_E97BC6396BD4A821 FOREIGN KEY (question_secrete_id) REFERENCES question_secrete (id)');
        $this->addSql('ALTER TABLE reponse_question ADD CONSTRAINT FK_E97BC639A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE absence_trash DROP FOREIGN KEY FK_65EF2456896DBBDE');
        $this->addSql('ALTER TABLE absence_trash DROP FOREIGN KEY FK_65EF24569F0C48B4');
        $this->addSql('ALTER TABLE absence_trash DROP FOREIGN KEY FK_65EF2456B03A8386');
        $this->addSql('ALTER TABLE absence_trash DROP FOREIGN KEY FK_65EF2456E2C35FC');
        $this->addSql('ALTER TABLE evaluation_trash DROP FOREIGN KEY FK_C0DFC6BC896DBBDE');
        $this->addSql('ALTER TABLE evaluation_trash DROP FOREIGN KEY FK_C0DFC6BC98FB19AE');
        $this->addSql('ALTER TABLE evaluation_trash DROP FOREIGN KEY FK_C0DFC6BC9F0C48B4');
        $this->addSql('ALTER TABLE evaluation_trash DROP FOREIGN KEY FK_C0DFC6BCB03A8386');
        $this->addSql('ALTER TABLE evaluation_trash DROP FOREIGN KEY FK_C0DFC6BCCDF80196');
        $this->addSql('ALTER TABLE registration_history_trash DROP FOREIGN KEY FK_C03F4B5B896DBBDE');
        $this->addSql('ALTER TABLE registration_history_trash DROP FOREIGN KEY FK_C03F4B5B9F0C48B4');
        $this->addSql('ALTER TABLE registration_history_trash DROP FOREIGN KEY FK_C03F4B5BB03A8386');
        $this->addSql('ALTER TABLE registration_history_trash DROP FOREIGN KEY FK_C03F4B5BD2EECC3F');
        $this->addSql('ALTER TABLE registration_trash DROP FOREIGN KEY FK_8A821BA5896DBBDE');
        $this->addSql('ALTER TABLE registration_trash DROP FOREIGN KEY FK_8A821BA59F0C48B4');
        $this->addSql('ALTER TABLE registration_trash DROP FOREIGN KEY FK_8A821BA5B03A8386');
        $this->addSql('ALTER TABLE registration_trash DROP FOREIGN KEY FK_8A821BA5D2EECC3F');
        $this->addSql('ALTER TABLE report_trash DROP FOREIGN KEY FK_2F34BC349F0C48B4');
        $this->addSql('ALTER TABLE report_trash DROP FOREIGN KEY FK_2F34BC34E2C35FC');
        $this->addSql('ALTER TABLE schedule DROP FOREIGN KEY FK_5A3811FB9C24126');
        $this->addSql('ALTER TABLE schedule DROP FOREIGN KEY FK_5A3811FB9FB69660');
        $this->addSql('ALTER TABLE schedule DROP FOREIGN KEY FK_5A3811FBCDF80196');
        $this->addSql('ALTER TABLE student_trash DROP FOREIGN KEY FK_8D1FC2D026397C6E');
        $this->addSql('ALTER TABLE student_trash DROP FOREIGN KEY FK_8D1FC2D03F192FC');
        $this->addSql('ALTER TABLE student_trash DROP FOREIGN KEY FK_8D1FC2D05A2DB2A0');
        $this->addSql('ALTER TABLE student_trash DROP FOREIGN KEY FK_8D1FC2D09E2F9937');
        $this->addSql('ALTER TABLE student_trash DROP FOREIGN KEY FK_8D1FC2D0C298691B');
        $this->addSql('ALTER TABLE student_trash DROP FOREIGN KEY FK_8D1FC2D0C76F1F52');
        $this->addSql('ALTER TABLE student_trash DROP FOREIGN KEY FK_8D1FC2D0D2EECC3F');
        $this->addSql('ALTER TABLE student_trash DROP FOREIGN KEY FK_8D1FC2D0D897C0C5');
        $this->addSql('ALTER TABLE student_trash DROP FOREIGN KEY FK_8D1FC2D0FFC5CFA7');
        $this->addSql('ALTER TABLE time_division DROP FOREIGN KEY FK_B49204D8781B17A6');
        $this->addSql('ALTER TABLE time_division DROP FOREIGN KEY FK_B49204D8D2EECC3F');
        $this->addSql('ALTER TABLE time_division DROP FOREIGN KEY FK_B49204D8D5883983');
        $this->addSql('ALTER TABLE time_division_number DROP FOREIGN KEY FK_55C85A5ED2EECC3F');
        $this->addSql('DROP TABLE absence_trash');
        $this->addSql('DROP TABLE activation_keys');
        $this->addSql('DROP TABLE club');
        $this->addSql('DROP TABLE evaluation_trash');
        $this->addSql('DROP TABLE registration_history_trash');
        $this->addSql('DROP TABLE registration_trash');
        $this->addSql('DROP TABLE report_trash');
        $this->addSql('DROP TABLE schedule');
        $this->addSql('DROP TABLE student_trash');
        $this->addSql('DROP TABLE time_division');
        $this->addSql('DROP TABLE time_division_action');
        $this->addSql('DROP TABLE time_division_number');
        $this->addSql('ALTER TABLE absence ADD slug VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE absence_teacher ADD slug VARCHAR(255) DEFAULT NULL, ADD supprime TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE classroom ADD slug VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE decision ADD slug VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE department ADD slug VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE diploma ADD slug VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE duty ADD slug VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE grade ADD slug VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE lesson ADD slug VARCHAR(255) DEFAULT NULL, ADD nbre_lesson_theorique_faite_avec_ressource_seq1 INT DEFAULT NULL, ADD nbre_lesson_theorique_faite_avec_ressource_seq2 INT DEFAULT NULL, ADD nbre_lesson_theorique_faite_avec_ressource_seq3 INT DEFAULT NULL, ADD nbre_lesson_theorique_faite_avec_ressource_seq4 INT DEFAULT NULL, ADD nbre_lesson_theorique_faite_avec_ressource_seq5 INT DEFAULT NULL, ADD nbre_lesson_theorique_faite_avec_ressource_seq6 INT DEFAULT NULL, ADD nbre_lesson_pratique_faite_avec_ressource_seq1 INT DEFAULT NULL, ADD nbre_lesson_pratique_faite_avec_ressource_seq2 INT DEFAULT NULL, ADD nbre_lesson_pratique_faite_avec_ressource_seq3 INT DEFAULT NULL, ADD nbre_lesson_pratique_faite_avec_ressource_seq4 INT DEFAULT NULL, ADD nbre_lesson_pratique_faite_avec_ressource_seq5 INT DEFAULT NULL, ADD nbre_lesson_pratique_faite_avec_ressource_seq6 INT DEFAULT NULL');
        $this->addSql('ALTER TABLE skill ADD sequence_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE skill ADD CONSTRAINT FK_5E3DE47798FB19AE FOREIGN KEY (sequence_id) REFERENCES sequence (id)');
        $this->addSql('CREATE INDEX IDX_5E3DE47798FB19AE ON skill (sequence_id)');
        $this->addSql('ALTER TABLE student ADD deleted_by_id INT DEFAULT NULL, ADD slug VARCHAR(255) DEFAULT NULL, ADD supprime TINYINT(1) NOT NULL, ADD deleted_at DATETIME DEFAULT NULL, ADD qr_code_roll_of_honor VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE student ADD CONSTRAINT FK_B723AF33C76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_B723AF33C76F1F52 ON student (deleted_by_id)');
        $this->addSql('ALTER TABLE sub_system DROP photo');
        $this->addSql('ALTER TABLE subject ADD slug VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE teacher ADD slug VARCHAR(255) DEFAULT NULL, ADD supprime TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE term ADD slug VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE time_table ADD slug VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user DROP activation_key, DROP cle_en_claire');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE absence_trash (id INT AUTO_INCREMENT NOT NULL, term_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, student_trash_id INT DEFAULT NULL, absence INT DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_65EF2456E2C35FC (term_id), INDEX IDX_65EF2456B03A8386 (created_by_id), INDEX IDX_65EF2456896DBBDE (updated_by_id), INDEX IDX_65EF24569F0C48B4 (student_trash_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE activation_keys (id INT AUTO_INCREMENT NOT NULL, activation_key VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, cle_en_clair VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE club (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE evaluation_trash (id INT AUTO_INCREMENT NOT NULL, sequence_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, lesson_id INT DEFAULT NULL, student_trash_id INT DEFAULT NULL, mark DOUBLE PRECISION NOT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_C0DFC6BC98FB19AE (sequence_id), INDEX IDX_C0DFC6BCB03A8386 (created_by_id), INDEX IDX_C0DFC6BC896DBBDE (updated_by_id), INDEX IDX_C0DFC6BCCDF80196 (lesson_id), INDEX IDX_C0DFC6BC9F0C48B4 (student_trash_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE registration_history_trash (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, school_year_id INT DEFAULT NULL, student_trash_id INT DEFAULT NULL, school_fees INT DEFAULT NULL, apee_fees INT DEFAULT NULL, computer_fees INT DEFAULT NULL, medical_booklet_fees INT DEFAULT NULL, clean_school_fees INT DEFAULT NULL, photo_fees INT DEFAULT NULL, stamp_fees INT DEFAULT NULL, exam_fees INT DEFAULT NULL, created_at DATETIME DEFAULT NULL, INDEX IDX_C03F4B5BB03A8386 (created_by_id), INDEX IDX_C03F4B5B896DBBDE (updated_by_id), INDEX IDX_C03F4B5BD2EECC3F (school_year_id), INDEX IDX_C03F4B5B9F0C48B4 (student_trash_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE registration_trash (id INT AUTO_INCREMENT NOT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, school_year_id INT DEFAULT NULL, student_trash_id INT DEFAULT NULL, school_fees INT DEFAULT NULL, apee_fees INT DEFAULT NULL, computer_fees INT DEFAULT NULL, medical_booklet_fees INT DEFAULT NULL, clean_school_fees INT DEFAULT NULL, photo_fees INT DEFAULT NULL, stamp_fees INT DEFAULT NULL, exam_fees INT DEFAULT NULL, created_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_8A821BA5B03A8386 (created_by_id), INDEX IDX_8A821BA5896DBBDE (updated_by_id), INDEX IDX_8A821BA5D2EECC3F (school_year_id), INDEX IDX_8A821BA59F0C48B4 (student_trash_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE report_trash (id INT AUTO_INCREMENT NOT NULL, term_id INT DEFAULT NULL, student_trash_id INT DEFAULT NULL, moyenne DOUBLE PRECISION NOT NULL, rang INT NOT NULL, INDEX IDX_2F34BC34E2C35FC (term_id), INDEX IDX_2F34BC349F0C48B4 (student_trash_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE schedule (id INT AUTO_INCREMENT NOT NULL, day_id INT DEFAULT NULL, time_division_id INT DEFAULT NULL, lesson_id INT DEFAULT NULL, INDEX IDX_5A3811FB9C24126 (day_id), INDEX IDX_5A3811FB9FB69660 (time_division_id), INDEX IDX_5A3811FBCDF80196 (lesson_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE student_trash (id INT AUTO_INCREMENT NOT NULL, sex_id INT DEFAULT NULL, school_year_id INT DEFAULT NULL, deleted_by_id INT DEFAULT NULL, repeater_id INT DEFAULT NULL, operateur_id INT DEFAULT NULL, mode_admission_id INT DEFAULT NULL, classe_entree_id INT DEFAULT NULL, classe_frere_soeur_id INT DEFAULT NULL, sub_system_id INT DEFAULT NULL, full_name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, birthday DATE NOT NULL, birthplace VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, photo VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, registration_number VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, telephone_pere VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, father_name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, mother_name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, deleted_at DATETIME DEFAULT NULL, updated_at DATETIME DEFAULT NULL, classroom VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, qr_code VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, numero_hcr VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, profession_pere VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, profession_mere VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, tuteur VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, telephone_tuteur VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, personne_acontacter_en_cas_uergence VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, telephone_personne_en_cas_urgence VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, date_premiere_entree_etablissement_at DATETIME DEFAULT NULL, etablisement_frequente_an_dernier VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, drepanocytose TINYINT(1) DEFAULT NULL, apte TINYINT(1) DEFAULT NULL, asthme TINYINT(1) DEFAULT NULL, covid TINYINT(1) DEFAULT NULL, autres_maladies VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, allergie TINYINT(1) DEFAULT NULL, si_oui_allergie VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, groupe_sanguin VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, rhesus VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, club_multiculturel TINYINT(1) DEFAULT NULL, club_scientifique TINYINT(1) DEFAULT NULL, club_journal TINYINT(1) DEFAULT NULL, club_environnement TINYINT(1) DEFAULT NULL, club_sante TINYINT(1) DEFAULT NULL, club_rethorique TINYINT(1) DEFAULT NULL, autre_club VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, frere TINYINT(1) DEFAULT NULL, soeur TINYINT(1) DEFAULT NULL, enseignant TINYINT(1) DEFAULT NULL, autre_connaisance_etablissement VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, nom_personne_etablissement VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, telephone_personne_etablissement VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, autochtone TINYINT(1) DEFAULT NULL, telephone_mere VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, qr_code_fiche VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, club_bilingue TINYINT(1) DEFAULT NULL, club_lv2 TINYINT(1) DEFAULT NULL, numero_whatsapp VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, solvable TINYINT(1) DEFAULT NULL, INDEX IDX_8D1FC2D05A2DB2A0 (sex_id), INDEX IDX_8D1FC2D0D2EECC3F (school_year_id), INDEX IDX_8D1FC2D0C76F1F52 (deleted_by_id), INDEX IDX_8D1FC2D026397C6E (repeater_id), INDEX IDX_8D1FC2D03F192FC (operateur_id), INDEX IDX_8D1FC2D0FFC5CFA7 (mode_admission_id), INDEX IDX_8D1FC2D09E2F9937 (classe_entree_id), INDEX IDX_8D1FC2D0D897C0C5 (classe_frere_soeur_id), INDEX IDX_8D1FC2D0C298691B (sub_system_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE time_division (id INT AUTO_INCREMENT NOT NULL, time_division_action_id INT DEFAULT NULL, time_division_number_id INT DEFAULT NULL, school_year_id INT DEFAULT NULL, start_time TIME NOT NULL, end_time TIME NOT NULL, INDEX IDX_B49204D8781B17A6 (time_division_action_id), INDEX IDX_B49204D8D5883983 (time_division_number_id), INDEX IDX_B49204D8D2EECC3F (school_year_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE time_division_action (id INT AUTO_INCREMENT NOT NULL, time_division_action VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE time_division_number (id INT AUTO_INCREMENT NOT NULL, school_year_id INT DEFAULT NULL, time_division_number INT NOT NULL, is_used TINYINT(1) NOT NULL, INDEX IDX_55C85A5ED2EECC3F (school_year_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE absence_trash ADD CONSTRAINT FK_65EF2456896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE absence_trash ADD CONSTRAINT FK_65EF24569F0C48B4 FOREIGN KEY (student_trash_id) REFERENCES student_trash (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE absence_trash ADD CONSTRAINT FK_65EF2456B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE absence_trash ADD CONSTRAINT FK_65EF2456E2C35FC FOREIGN KEY (term_id) REFERENCES term (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE evaluation_trash ADD CONSTRAINT FK_C0DFC6BC896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE evaluation_trash ADD CONSTRAINT FK_C0DFC6BC98FB19AE FOREIGN KEY (sequence_id) REFERENCES sequence (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE evaluation_trash ADD CONSTRAINT FK_C0DFC6BC9F0C48B4 FOREIGN KEY (student_trash_id) REFERENCES student_trash (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE evaluation_trash ADD CONSTRAINT FK_C0DFC6BCB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE evaluation_trash ADD CONSTRAINT FK_C0DFC6BCCDF80196 FOREIGN KEY (lesson_id) REFERENCES lesson (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE registration_history_trash ADD CONSTRAINT FK_C03F4B5B896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE registration_history_trash ADD CONSTRAINT FK_C03F4B5B9F0C48B4 FOREIGN KEY (student_trash_id) REFERENCES student_trash (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE registration_history_trash ADD CONSTRAINT FK_C03F4B5BB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE registration_history_trash ADD CONSTRAINT FK_C03F4B5BD2EECC3F FOREIGN KEY (school_year_id) REFERENCES school_year (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE registration_trash ADD CONSTRAINT FK_8A821BA5896DBBDE FOREIGN KEY (updated_by_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE registration_trash ADD CONSTRAINT FK_8A821BA59F0C48B4 FOREIGN KEY (student_trash_id) REFERENCES student_trash (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE registration_trash ADD CONSTRAINT FK_8A821BA5B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE registration_trash ADD CONSTRAINT FK_8A821BA5D2EECC3F FOREIGN KEY (school_year_id) REFERENCES school_year (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE report_trash ADD CONSTRAINT FK_2F34BC349F0C48B4 FOREIGN KEY (student_trash_id) REFERENCES student_trash (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE report_trash ADD CONSTRAINT FK_2F34BC34E2C35FC FOREIGN KEY (term_id) REFERENCES term (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE schedule ADD CONSTRAINT FK_5A3811FB9C24126 FOREIGN KEY (day_id) REFERENCES day (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE schedule ADD CONSTRAINT FK_5A3811FB9FB69660 FOREIGN KEY (time_division_id) REFERENCES time_division (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE schedule ADD CONSTRAINT FK_5A3811FBCDF80196 FOREIGN KEY (lesson_id) REFERENCES lesson (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE student_trash ADD CONSTRAINT FK_8D1FC2D026397C6E FOREIGN KEY (repeater_id) REFERENCES repeater (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE student_trash ADD CONSTRAINT FK_8D1FC2D03F192FC FOREIGN KEY (operateur_id) REFERENCES operateur (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE student_trash ADD CONSTRAINT FK_8D1FC2D05A2DB2A0 FOREIGN KEY (sex_id) REFERENCES sex (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE student_trash ADD CONSTRAINT FK_8D1FC2D09E2F9937 FOREIGN KEY (classe_entree_id) REFERENCES classroom (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE student_trash ADD CONSTRAINT FK_8D1FC2D0C298691B FOREIGN KEY (sub_system_id) REFERENCES sub_system (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE student_trash ADD CONSTRAINT FK_8D1FC2D0C76F1F52 FOREIGN KEY (deleted_by_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE student_trash ADD CONSTRAINT FK_8D1FC2D0D2EECC3F FOREIGN KEY (school_year_id) REFERENCES school_year (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE student_trash ADD CONSTRAINT FK_8D1FC2D0D897C0C5 FOREIGN KEY (classe_frere_soeur_id) REFERENCES classroom (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE student_trash ADD CONSTRAINT FK_8D1FC2D0FFC5CFA7 FOREIGN KEY (mode_admission_id) REFERENCES mode_admission (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE time_division ADD CONSTRAINT FK_B49204D8781B17A6 FOREIGN KEY (time_division_action_id) REFERENCES time_division_action (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE time_division ADD CONSTRAINT FK_B49204D8D2EECC3F FOREIGN KEY (school_year_id) REFERENCES school_year (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE time_division ADD CONSTRAINT FK_B49204D8D5883983 FOREIGN KEY (time_division_number_id) REFERENCES time_division_number (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE time_division_number ADD CONSTRAINT FK_55C85A5ED2EECC3F FOREIGN KEY (school_year_id) REFERENCES school_year (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE conseil DROP FOREIGN KEY FK_3F3F0681E2C35FC');
        $this->addSql('ALTER TABLE conseil DROP FOREIGN KEY FK_3F3F0681CB944F1A');
        $this->addSql('ALTER TABLE conseil DROP FOREIGN KEY FK_3F3F0681B03A8386');
        $this->addSql('ALTER TABLE conseil DROP FOREIGN KEY FK_3F3F0681896DBBDE');
        $this->addSql('ALTER TABLE historique_teacher DROP FOREIGN KEY FK_D2E2F9AA41807E1D');
        $this->addSql('ALTER TABLE historique_teacher DROP FOREIGN KEY FK_D2E2F9AA9C24126');
        $this->addSql('ALTER TABLE historique_teacher DROP FOREIGN KEY FK_D2E2F9AA23EDC87');
        $this->addSql('ALTER TABLE historique_teacher DROP FOREIGN KEY FK_D2E2F9AAC298691B');
        $this->addSql('ALTER TABLE historique_teacher DROP FOREIGN KEY FK_D2E2F9AA6278D5A8');
        $this->addSql('ALTER TABLE historique_teacher DROP FOREIGN KEY FK_D2E2F9AACB5FDB3E');
        $this->addSql('ALTER TABLE historique_teacher DROP FOREIGN KEY FK_D2E2F9AA98FB19AE');
        $this->addSql('ALTER TABLE reponse_question DROP FOREIGN KEY FK_E97BC6396BD4A821');
        $this->addSql('ALTER TABLE reponse_question DROP FOREIGN KEY FK_E97BC639A76ED395');
        $this->addSql('DROP TABLE conseil');
        $this->addSql('DROP TABLE historique_teacher');
        $this->addSql('DROP TABLE question_secrete');
        $this->addSql('DROP TABLE reponse_question');
        $this->addSql('ALTER TABLE absence DROP slug');
        $this->addSql('ALTER TABLE absence_teacher DROP slug, DROP supprime');
        $this->addSql('ALTER TABLE classroom DROP slug');
        $this->addSql('ALTER TABLE decision DROP slug');
        $this->addSql('ALTER TABLE department DROP slug');
        $this->addSql('ALTER TABLE diploma DROP slug');
        $this->addSql('ALTER TABLE duty DROP slug');
        $this->addSql('ALTER TABLE grade DROP slug');
        $this->addSql('ALTER TABLE lesson DROP slug, DROP nbre_lesson_theorique_faite_avec_ressource_seq1, DROP nbre_lesson_theorique_faite_avec_ressource_seq2, DROP nbre_lesson_theorique_faite_avec_ressource_seq3, DROP nbre_lesson_theorique_faite_avec_ressource_seq4, DROP nbre_lesson_theorique_faite_avec_ressource_seq5, DROP nbre_lesson_theorique_faite_avec_ressource_seq6, DROP nbre_lesson_pratique_faite_avec_ressource_seq1, DROP nbre_lesson_pratique_faite_avec_ressource_seq2, DROP nbre_lesson_pratique_faite_avec_ressource_seq3, DROP nbre_lesson_pratique_faite_avec_ressource_seq4, DROP nbre_lesson_pratique_faite_avec_ressource_seq5, DROP nbre_lesson_pratique_faite_avec_ressource_seq6');
        $this->addSql('ALTER TABLE skill DROP FOREIGN KEY FK_5E3DE47798FB19AE');
        $this->addSql('DROP INDEX IDX_5E3DE47798FB19AE ON skill');
        $this->addSql('ALTER TABLE skill DROP sequence_id');
        $this->addSql('ALTER TABLE student DROP FOREIGN KEY FK_B723AF33C76F1F52');
        $this->addSql('DROP INDEX IDX_B723AF33C76F1F52 ON student');
        $this->addSql('ALTER TABLE student DROP deleted_by_id, DROP slug, DROP supprime, DROP deleted_at, DROP qr_code_roll_of_honor');
        $this->addSql('ALTER TABLE sub_system ADD photo VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE subject DROP slug');
        $this->addSql('ALTER TABLE teacher DROP slug, DROP supprime');
        $this->addSql('ALTER TABLE term DROP slug');
        $this->addSql('ALTER TABLE time_table DROP slug');
        $this->addSql('ALTER TABLE user ADD activation_key VARCHAR(255) DEFAULT NULL, ADD cle_en_claire VARCHAR(255) DEFAULT NULL');
    }
}
