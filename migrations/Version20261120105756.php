<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261120105756 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create CatAPI breed and taxonomy tables for the The Cat API ETL.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE country (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5373C9665E237E06 ON country (name)');
        $this->addSql('CREATE TABLE "group" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6DC044C55E237E06 ON "group" (name)');
        $this->addSql('CREATE TABLE temperament (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3BEECA65E237E06 ON temperament (name)');
        $this->addSql('CREATE TABLE breed (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, cat_api_id VARCHAR(50) NOT NULL, name VARCHAR(255) NOT NULL, life_span VARCHAR(20) DEFAULT NULL, description CLOB DEFAULT NULL, history CLOB DEFAULT NULL, weight INTEGER DEFAULT NULL, height INTEGER DEFAULT NULL, country_id_id INTEGER DEFAULT NULL, group_id_id INTEGER DEFAULT NULL, CONSTRAINT FK_F8AF884FD8A48BBD FOREIGN KEY (country_id_id) REFERENCES country (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_F8AF884F2F68B530 FOREIGN KEY (group_id_id) REFERENCES "group" (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F8AF884F8A91DC4D ON breed (cat_api_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F8AF884F5E237E06 ON breed (name)');
        $this->addSql('CREATE INDEX IDX_F8AF884FD8A48BBD ON breed (country_id_id)');
        $this->addSql('CREATE INDEX IDX_F8AF884F2F68B530 ON breed (group_id_id)');
        $this->addSql('CREATE TABLE breed_temperament (breed_id INTEGER NOT NULL, temperament_id INTEGER NOT NULL, PRIMARY KEY (breed_id, temperament_id), CONSTRAINT FK_A7AB92DDA8B4A30F FOREIGN KEY (breed_id) REFERENCES breed (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_A7AB92DD65A6C68A FOREIGN KEY (temperament_id) REFERENCES temperament (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_A7AB92DDA8B4A30F ON breed_temperament (breed_id)');
        $this->addSql('CREATE INDEX IDX_A7AB92DD65A6C68A ON breed_temperament (temperament_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE breed_temperament');
        $this->addSql('DROP TABLE breed');
        $this->addSql('DROP TABLE country');
        $this->addSql('DROP TABLE "group"');
        $this->addSql('DROP TABLE temperament');
    }
}
