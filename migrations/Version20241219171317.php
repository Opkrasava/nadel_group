<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241219171317 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE products CHANGE unit_measurement unit_measurement_id INT NOT NULL');
        $this->addSql('ALTER TABLE products ADD CONSTRAINT FK_B3BA5A5AE24AEC2B FOREIGN KEY (unit_measurement_id) REFERENCES unit_measurement (id)');
        $this->addSql('CREATE INDEX IDX_B3BA5A5AE24AEC2B ON products (unit_measurement_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE products DROP FOREIGN KEY FK_B3BA5A5AE24AEC2B');
        $this->addSql('DROP INDEX IDX_B3BA5A5AE24AEC2B ON products');
        $this->addSql('ALTER TABLE products CHANGE unit_measurement_id unit_measurement INT NOT NULL');
    }
}
