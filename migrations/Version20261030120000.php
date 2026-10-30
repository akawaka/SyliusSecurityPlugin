<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Sylius\Bundle\CoreBundle\Doctrine\Migrations\AbstractMigration;

final class Version20261030120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Removes the Doctrine DBAL 3 type comment from the administrator action log creation date.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE akawaka_admin_action_log CHANGE created_at created_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE akawaka_admin_action_log CHANGE created_at created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
