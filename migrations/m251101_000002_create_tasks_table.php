<?php

use yii\db\Migration;

class m251101_000002_create_tasks_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%tasks}}', [
            'id' => $this->primaryKey(),
            'title' => $this->string(255)->notNull(),
            'description' => $this->text()->null(),
            'status' => "ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending'",
            'priority' => "ENUM('low','medium','high') NOT NULL DEFAULT 'medium'",
            'due_date' => $this->date()->null(),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
            'updated_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),
        ]);

        // seed example tasks (optional)
        $this->insert('{{%tasks}}', [
            'title' => 'Example task 1',
            'description' => 'This is a seeded task for testing.',
            'status' => 'pending',
            'priority' => 'high',
            'due_date' => date('Y-m-d', strtotime('+3 days')),
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%tasks}}');
    }
}
