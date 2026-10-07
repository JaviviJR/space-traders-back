<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261007162448 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE agents ADD created_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE agents ADD updated_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE agents ALTER created_at DROP NOT NULL');
        $this->addSql('ALTER TABLE agents ALTER updated_at DROP NOT NULL');
        $this->addSql('ALTER TABLE agents ADD CONSTRAINT FK_9596AB6EB03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE agents ADD CONSTRAINT FK_9596AB6E896DBBDE FOREIGN KEY (updated_by_id) REFERENCES users (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_9596AB6EB03A8386 ON agents (created_by_id)');
        $this->addSql('CREATE INDEX IDX_9596AB6E896DBBDE ON agents (updated_by_id)');
        $this->addSql('ALTER TABLE refresh_tokens ALTER created_at DROP NOT NULL');
        $this->addSql('ALTER TABLE users ALTER created_at DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE agents DROP CONSTRAINT FK_9596AB6EB03A8386');
        $this->addSql('ALTER TABLE agents DROP CONSTRAINT FK_9596AB6E896DBBDE');
        $this->addSql('DROP INDEX IDX_9596AB6EB03A8386');
        $this->addSql('DROP INDEX IDX_9596AB6E896DBBDE');
        $this->addSql('ALTER TABLE agents DROP created_by_id');
        $this->addSql('ALTER TABLE agents DROP updated_by_id');
        $this->addSql('ALTER TABLE agents ALTER created_at SET NOT NULL');
        $this->addSql('ALTER TABLE agents ALTER updated_at SET NOT NULL');
        $this->addSql('ALTER TABLE refresh_tokens ALTER created_at SET NOT NULL');
        $this->addSql('ALTER TABLE users ALTER created_at SET NOT NULL');
    }
}
