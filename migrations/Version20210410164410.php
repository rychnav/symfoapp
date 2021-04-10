<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210410164410 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add ConfirmToken entity';
    }

    public function up(Schema $schema): void
    {
        // This `up()` migration is auto-generated, please modify it to your needs.
        $this->addSql('
            CREATE TABLE confirm_token 
            (
                id INT AUTO_INCREMENT NOT NULL, 
                token VARCHAR(255) NOT NULL, 
                public_token VARCHAR(255) NOT NULL, 
                expires_at DATETIME NOT NULL, 
                PRIMARY KEY(id)
            ) 
            DEFAULT CHARACTER SET utf8mb4 
            COLLATE `utf8mb4_unicode_ci` 
            ENGINE = InnoDB
        ');

        $this->addSql('
            ALTER TABLE user 
                ADD token_id INT DEFAULT NULL, 
                ADD register_at DATETIME NOT NULL, 
                ADD confirmed_at DATETIME DEFAULT NULL
        ');

        $this->addSql('
            ALTER TABLE user 
                ADD CONSTRAINT FK_8D93D64941DEE7B9 
                    FOREIGN KEY (token_id) REFERENCES confirm_token (id) ON DELETE SET NULL
        ');

        $this->addSql('
            CREATE UNIQUE INDEX UNIQ_8D93D64941DEE7B9 ON user (token_id)
        ');
    }

    public function down(Schema $schema): void
    {
        // This `down()` migration is auto-generated, please modify it to your needs.
        $this->addSql('
            ALTER TABLE user 
                DROP FOREIGN KEY FK_8D93D64941DEE7B9
        ');

        $this->addSql('
            DROP TABLE confirm_token
        ');

        $this->addSql('
            DROP INDEX UNIQ_8D93D64941DEE7B9 ON user
        ');

        $this->addSql('
            ALTER TABLE user 
                DROP token_id, 
                DROP register_at, 
                DROP confirmed_at
        ');
    }
}
