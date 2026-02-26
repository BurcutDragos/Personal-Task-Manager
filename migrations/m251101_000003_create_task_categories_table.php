<?php

use yii\db\Migration;

class m251101_000003_create_task_categories_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%task_categories}}', [
            'task_id' => $this->integer()->notNull(),
            'category_id' => $this->integer()->notNull(),
            'PRIMARY KEY(task_id, category_id)',
        ]);

        // foreign keys
        $this->addForeignKey('fk_taskcat_task', '{{%task_categories}}', 'task_id', '{{%tasks}}', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk_taskcat_category', '{{%task_categories}}', 'category_id', '{{%categories}}', 'id', 'CASCADE', 'CASCADE');

        // Associate seeded task with a category (if exist)
        $taskId = (new \yii\db\Query())->select('id')->from('{{%tasks}}')->limit(1)->scalar();
        $catId = (new \yii\db\Query())->select('id')->from('{{%categories}}')->limit(1)->scalar();
        if ($taskId && $catId) {
            $this->insert('{{%task_categories}}', ['task_id' => $taskId, 'category_id' => $catId]);
        }
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_taskcat_task', '{{%task_categories}}');
        $this->dropForeignKey('fk_taskcat_category', '{{%task_categories}}');
        $this->dropTable('{{%task_categories}}');
    }
}
