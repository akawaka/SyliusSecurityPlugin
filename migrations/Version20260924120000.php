<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Sylius\Bundle\CoreBundle\Doctrine\Migrations\AbstractMigration;

final class Version20260924120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Creates the administrator action log table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE akawaka_admin_action_log (id INT AUTO_INCREMENT NOT NULL, admin_user_id INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', type VARCHAR(32) NOT NULL, admin_email VARCHAR(255) DEFAULT NULL, route VARCHAR(255) DEFAULT NULL, context VARCHAR(64) DEFAULT NULL, method VARCHAR(10) NOT NULL, uri LONGTEXT NOT NULL, route_parameters JSON NOT NULL, status_code SMALLINT DEFAULT NULL, ip VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(512) DEFAULT NULL, INDEX IDX_61A8DD16352511C (admin_user_id), INDEX IDX_AKAWAKA_ADMIN_ACTION_LOG_CREATED_AT (created_at), INDEX IDX_AKAWAKA_ADMIN_ACTION_LOG_TYPE (type), INDEX IDX_AKAWAKA_ADMIN_ACTION_LOG_ADMIN_EMAIL (admin_email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE akawaka_admin_action_log ADD CONSTRAINT FK_61A8DD16352511C FOREIGN KEY (admin_user_id) REFERENCES sylius_admin_user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE akawaka_admin_action_log DROP FOREIGN KEY FK_61A8DD16352511C');
        $this->addSql('DROP TABLE akawaka_admin_action_log');
    }
}
