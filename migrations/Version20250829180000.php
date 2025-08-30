<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250829180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create rates table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS rates (
            id VARCHAR(36) NOT NULL PRIMARY KEY,
            pair VARCHAR(20) NOT NULL,
            price NUMERIC(38, 18) NOT NULL COMMENT \'(DC2Type:decimal_type)\',
            created_at DATETIME NOT NULL,
            INDEX idx_pair_created_at (pair, created_at)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS rates');
    }
}
