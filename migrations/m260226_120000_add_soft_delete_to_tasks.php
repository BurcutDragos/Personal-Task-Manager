<?php

use yii\db\Migration;

/**
 * Migration: Add soft-delete support to the tasks table.
 *
 * Instead of permanently removing a task when the user deletes it,
 * we simply set a timestamp in the `deleted_at` column.  A NULL value
 * means the task is active; a non-NULL value means it has been soft-deleted
 * and will be hidden from normal views but can be restored from the Trash.
 */
class m260226_120000_add_soft_delete_to_tasks extends Migration
{
    /**
     * Applies the migration: adds the `deleted_at` nullable timestamp column.
     */
    public function safeUp()
    {
        $this->addColumn(
            '{{%tasks}}',
            'deleted_at',
            $this->timestamp()
                ->null()
                ->defaultValue(null)
                ->comment('NULL = active task; timestamp = soft-deleted at that time')
        );
    }

    /**
     * Reverts the migration: removes the `deleted_at` column.
     */
    public function safeDown()
    {
        $this->dropColumn('{{%tasks}}', 'deleted_at');
    }
}
