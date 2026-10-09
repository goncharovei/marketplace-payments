<?php

declare(strict_types=1);

namespace Database\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261009071956 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE payouts (id VARCHAR(36) NOT NULL, seller_id VARCHAR(64) NOT NULL, order_id VARCHAR(36) NOT NULL, amount JSONB NOT NULL, platform_fee JSONB NOT NULL, method VARCHAR(32) NOT NULL, status VARCHAR(32) NOT NULL, external_id VARCHAR(255) DEFAULT NULL, retry_count INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE seller_balances (seller_id VARCHAR(64) NOT NULL, available JSONB NOT NULL, reserved JSONB NOT NULL, debt JSONB NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (seller_id))
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE payouts
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE seller_balances
        SQL);
    }
}
