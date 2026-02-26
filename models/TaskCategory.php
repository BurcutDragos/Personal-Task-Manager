<?php

/**
 * TaskCategory ActiveRecord model — the many-to-many pivot table.
 *
 * The `task_categories` table stores pairs of (task_id, category_id) to link
 * tasks and categories together.  A single task can have many categories, and
 * a single category can be attached to many tasks.
 *
 * This model is used internally by Task::afterSave() to insert/delete pivot rows
 * whenever category assignments are changed.  You generally do not interact with
 * it directly from controllers or views.
 */

namespace app\models;

use yii\db\ActiveRecord;

/**
 * Class TaskCategory
 *
 * @property int $task_id     Foreign key → tasks.id
 * @property int $category_id Foreign key → categories.id
 */
class TaskCategory extends ActiveRecord
{
    /**
     * Returns the name of the pivot table.
     *
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%task_categories}}';
    }

    /**
     * Validation rules for the pivot table.
     *
     * Both columns are required integers, and the combination of
     * (task_id, category_id) must be unique (enforced by the composite PK in the DB).
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            // Both IDs are required
            [['task_id', 'category_id'], 'required'],

            // Both IDs must be whole numbers
            [['task_id', 'category_id'], 'integer'],

            // The pair must be unique — prevents duplicate pivot rows
            [['task_id', 'category_id'], 'unique', 'targetAttribute' => ['task_id', 'category_id']],
        ];
    }
}
