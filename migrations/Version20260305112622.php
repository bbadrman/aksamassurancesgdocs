<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260305112622 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activity_log DROP FOREIGN KEY `FK_FD06F64719EB6921`');
        $this->addSql('DROP INDEX IDX_FD06F64719EB6921 ON activity_log');
        $this->addSql('ALTER TABLE activity_log CHANGE client_id contrat_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE activity_log ADD CONSTRAINT FK_FD06F6471823061F FOREIGN KEY (contrat_id) REFERENCES contrat (id)');
        $this->addSql('CREATE INDEX IDX_FD06F6471823061F ON activity_log (contrat_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE activity_log DROP FOREIGN KEY FK_FD06F6471823061F');
        $this->addSql('DROP INDEX IDX_FD06F6471823061F ON activity_log');
        $this->addSql('ALTER TABLE activity_log CHANGE contrat_id client_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE activity_log ADD CONSTRAINT `FK_FD06F64719EB6921` FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('CREATE INDEX IDX_FD06F64719EB6921 ON activity_log (client_id)');
    }
}
