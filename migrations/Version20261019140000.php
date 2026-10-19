<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * The unique constraints were declared inside #[ORM\Table(uniqueConstraints: ...)], which has no
 * effect in Doctrine ORM 3, so they never reached the database. They are now separate
 * #[ORM\UniqueConstraint] attributes; this migration creates them.
 */
final class Version20261019140000 extends AbstractMigration
{
    /** index name => [table, columns, hint when duplicates exist] */
    private const UNIQUE_INDEXES = [
        'uniq_set_part_color'   => ['lego_set_part', 'model_number, part_color_id, is_spare', 'Run "php bin/console app:lego:sync-spare-parts" first to mark spare parts.'],
        'uniq_set_list_set'     => ['lego_set_list_set', 'set_number, set_list_id', 'Remove the duplicate sets from the boards.'],
        'uniq_user_set_part'    => ['lego_user_set_part', 'set_list_set_id, set_part_id', 'Remove the duplicate part states.'],
        'uniq_user_set_minifig' => ['lego_user_set_minifig', 'set_list_set_id, set_minifig_id', 'Remove the duplicate minifig states.'],
        'uniq_part_color'       => ['lego_part_color', 'part_id, color_id', 'Merge the duplicate part colors.'],
        'uniq_user_set_rating'  => ['lego_set_rating', 'user_id, set_number', 'Remove the duplicate ratings.'],
    ];

    public function getDescription(): string
    {
        return 'Add the unique constraints that were declared on entities but never created';
    }

    public function preUp(Schema $schema): void
    {
        foreach (self::UNIQUE_INDEXES as $name => [$table, $columns, $hint]) {
            $duplicates = (int) $this->connection->fetchOne(
                "SELECT COUNT(*) FROM (SELECT 1 FROM $table GROUP BY $columns HAVING COUNT(*) > 1) d"
            );
            $this->abortIf(
                $duplicates > 0,
                sprintf('Cannot add %s: %d duplicate(s) in %s (%s). %s', $name, $duplicates, $table, $columns, $hint)
            );
        }
    }

    public function up(Schema $schema): void
    {
        foreach (self::UNIQUE_INDEXES as $name => [$table, $columns]) {
            $this->addSql("CREATE UNIQUE INDEX $name ON $table ($columns)");
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::UNIQUE_INDEXES as $name => [$table]) {
            $this->addSql("DROP INDEX $name ON $table");
        }
    }
}
