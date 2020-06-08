<?php
/**
 * Reasons plugin for Craft CMS 3.x
 *
 * Adds conditionals to field layouts.
 *
 * @link      https://vaersaagod.no
 * @copyright Copyright (c) 2020 Mats Mikkel Rummelhoff
 */

namespace mmikkel\reasons\migrations;

use mmikkel\reasons\Reasons;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;
use mmikkel\reasons\records\Conditionals;
use yii\db\Query;

/**
 * @author    Mats Mikkel Rummelhoff
 * @package   Reasons
 * @since     2.0.0
 */
class Install extends Migration
{
    // Public Properties
    // =========================================================================

    /**
     * @var string The database driver to use
     */
    public $driver;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;

        if ($this->createTables()) {
            $this->addForeignKeys();
            // Refresh the db schema caches
            Craft::$app->db->schema->refresh();
        }

        return true;
    }

   /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->driver = Craft::$app->getConfig()->getDb()->driver;
        $this->removeTables();

        return true;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @return bool
     */
    protected function createTables()
    {
        $tablesCreated = false;

        $tableSchema = Craft::$app->db->schema->getTableSchema('{{%reasons}}');

        if ($tableSchema === null) {
            $this->createTable(
                '{{%reasons}}',
                [
                    'id' => $this->primaryKey(),
                    'fieldLayoutId' => $this->integer()->notNull(),
                    'conditionals' => $this->text(),
                    'dateCreated' => $this->dateTime()->notNull(),
                    'dateUpdated' => $this->dateTime()->notNull(),
                    'uid' => $this->uid(),
                ]
            );
            $tablesCreated = true;
        }

        return $tablesCreated;
    }

    /**
     * @return void
     */
    protected function addForeignKeys()
    {
        $this->addForeignKey(
            $this->db->getForeignKeyName('{{%reasons}}', 'fieldLayoutId'),
            '{{%reasons}}',
            'fieldLayoutId',
            '{{%fieldlayouts}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * @return void
     */
    protected function removeTables()
    {
        $this->dropTableIfExists('{{%reasons}}');
    }
}
