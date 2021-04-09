<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210409191742 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add firstName field to User entity';
    }

    public function up(Schema $schema) : void
    {
        // This `up()` migration is auto-generated, please modify it to your needs.
        $this->addSql('
            ALTER TABLE user 
                ADD first_name VARCHAR(50) NOT NULL
        ');
    }

    public function down(Schema $schema) : void
    {
        // This `down()` migration is auto-generated, please modify it to your needs.
        $this->addSql('
            ALTER TABLE user 
                DROP first_name
        ');
    }
}
