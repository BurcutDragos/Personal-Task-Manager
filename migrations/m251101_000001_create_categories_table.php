<?php

use yii\db\Migration;

class m251101_000001_create_categories_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%categories}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(100)->notNull()->unique(),
            'color' => $this->string(7)->notNull()->defaultValue('#007bff'),
            'created_at' => $this->timestamp()->defaultExpression('CURRENT_TIMESTAMP'),
        ]);

        // seed example categories
        $this->batchInsert('{{%categories}}', ['name', 'color'], [
            ['Work', '#28a745'],
            ['Personal', '#17a2b8'],
            ['Urgent', '#dc3545'],
        ]);
    }

    public function safeDown()
    {
        $this->dropTable('{{%categories}}');
    }
}
