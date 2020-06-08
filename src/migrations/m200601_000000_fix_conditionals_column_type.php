<?php


namespace mmikkel\reasons\migrations;


use craft\db\Migration;

class m200601_000000_fix_conditionals_column_type extends Migration
{

    /**
     * @inheritDoc
     */
    public function safeUp()
    {
        $this->alterColumn('{{%reasons}}', 'conditionals', $this->text());
    }

    /**
     * @inheritDoc
     */
    public function safeDown()
    {
        echo "m200601_000000_fix_conditionals_column_type cannot be reverted.\n";
        return false;
    }

}
