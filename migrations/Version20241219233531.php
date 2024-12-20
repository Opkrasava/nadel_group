<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241219233531 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE recipes_products DROP FOREIGN KEY FK_DFDAD282FDF2B1FA');
        $this->addSql('ALTER TABLE recipes_products DROP FOREIGN KEY FK_DFDAD2826C8A81A9');
        $this->addSql('DROP TABLE recipes_products');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE recipes_products (recipes_id INT NOT NULL, products_id INT NOT NULL, INDEX IDX_DFDAD282FDF2B1FA (recipes_id), INDEX IDX_DFDAD2826C8A81A9 (products_id), PRIMARY KEY(recipes_id, products_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE recipes_products ADD CONSTRAINT FK_DFDAD282FDF2B1FA FOREIGN KEY (recipes_id) REFERENCES recipes (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE recipes_products ADD CONSTRAINT FK_DFDAD2826C8A81A9 FOREIGN KEY (products_id) REFERENCES products (id) ON UPDATE NO ACTION ON DELETE CASCADE');
    }
}
