<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210412193952 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add lastResetAt property to User entity';
    }

    public function up(Schema $schema): void
    {
        // This `up()` migration is auto-generated, please modify it to your needs.
        $this->addSql('
            ALTER TABLE user 
                ADD last_reset_at DATETIME DEFAULT NULL
        ');
    }

    public function down(Schema $schema): void
    {
        // This `down()` migration is auto-generated, please modify it to your needs.
        $this->addSql('
            ALTER TABLE user 
                DROP last_reset_at
        ');
    }
}
