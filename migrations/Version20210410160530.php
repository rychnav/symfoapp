<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210410160530 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add facebookId property to User entity';
    }

    public function up(Schema $schema) : void
    {
        // This `up()` migration is auto-generated, please modify it to your needs.
        $this->addSql('
            ALTER TABLE user 
                ADD facebook_id VARCHAR(255) DEFAULT NULL
        ');
    }

    public function down(Schema $schema) : void
    {
        // This `down()` migration is auto-generated, please modify it to your needs.
        $this->addSql('
            ALTER TABLE user 
                DROP facebook_id
        ');
    }
}
