<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260303214203 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE article (UniqueId VARCHAR(36) NOT NULL, Id VARCHAR(64) NOT NULL, DesComClear VARCHAR(255) NOT NULL, RealStock INT NOT NULL, SalePriceVatIncluded NUMERIC(10, 2) NOT NULL, SalePriceVatExcluded NUMERIC(10, 2) NOT NULL, xx_Annee VARCHAR(255) NOT NULL, xx_Vehicule VARCHAR(255) NOT NULL, xx_Marque VARCHAR(100) NOT NULL, sysModifiedDate DATETIME DEFAULT NULL, sysCreatedDate DATETIME DEFAULT NULL, FamilyName VARCHAR(100) NOT NULL, ExportStartedAt DATETIME DEFAULT NULL, FtpFinishedAt DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_23A0E662ABD43F2 (Id), INDEX idx_family (FamilyName), INDEX idx_marque (xx_Marque), PRIMARY KEY (UniqueId)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE import_job (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(10) NOT NULL, file_path VARCHAR(255) NOT NULL, total_rows INT NOT NULL, processed_rows INT NOT NULL, byte_offset BIGINT NOT NULL, last_message LONGTEXT DEFAULT NULL, started_at DATETIME NOT NULL, finished_at DATETIME DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE import_source (id INT AUTO_INCREMENT NOT NULL, directory_path VARCHAR(255) NOT NULL, file_name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE article');
        $this->addSql('DROP TABLE import_job');
        $this->addSql('DROP TABLE import_source');
    }
}
