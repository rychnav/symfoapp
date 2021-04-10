<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210410002925 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add authType and googleId fields to User entity. Password can be null.';
    }

    public function up(Schema $schema) : void
    {
        // This `up()` migration is auto-generated, please modify it to your needs.
        $this->addSql('
            ALTER TABLE user 
                ADD auth_type VARCHAR(20) DEFAULT NULL, 
                ADD google_id VARCHAR(255) DEFAULT NULL, 
                CHANGE password 
                    password VARCHAR(255) DEFAULT NULL
        ');
    }

    public function down(Schema $schema) : void
    {
        // This `down()` migration is auto-generated, please modify it to your needs.
        $this->addSql('
            ALTER TABLE user 
                DROP auth_type, 
                DROP google_id, 
                CHANGE password 
                    password VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`
        ');
    }
}
