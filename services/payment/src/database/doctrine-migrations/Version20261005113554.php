<?php

declare(strict_types=1);

namespace Database\DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261005113554 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE payments (id VARCHAR(36) NOT NULL, order_id VARCHAR(36) NOT NULL, buyer_id VARCHAR(64) NOT NULL, amount JSONB NOT NULL, method VARCHAR(32) NOT NULL, status VARCHAR(32) NOT NULL, external_id VARCHAR(255) DEFAULT NULL, refunded_amount JSONB NOT NULL, refunds JSONB NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY (id))
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE payments
        SQL);
    }
}
