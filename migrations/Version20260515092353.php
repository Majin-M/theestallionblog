<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260515092353 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE orders ADD shipping_firstname VARCHAR(100) NOT NULL, ADD shipping_lastname VARCHAR(100) NOT NULL, ADD shipping_address VARCHAR(255) NOT NULL, ADD shipping_postal_code VARCHAR(10) NOT NULL, ADD shipping_city VARCHAR(100) NOT NULL, ADD shipping_country VARCHAR(100) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `orders` DROP shipping_firstname, DROP shipping_lastname, DROP shipping_address, DROP shipping_postal_code, DROP shipping_city, DROP shipping_country');
    }
}
