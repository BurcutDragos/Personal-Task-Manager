<?php

/**
 * Category ActiveRecord model.
 *
 * Represents a named, colour-coded label that can be attached to one or more
 * tasks.  The relationship between tasks and categories is many-to-many,
 * managed through the `task_categories` pivot table.
 */

namespace app\models;

use yii\db\ActiveRecord;
use yii\db\ActiveQuery;

/**
 * Class Category
 *
 * @property int    $id         Auto-incrementing primary key
 * @property string $name       Unique category name (max 100 chars, required)
 * @property string $color      Hex colour code (e.g. '#007bff'), used for badge styling
 * @property string $created_at Timestamp set automatically by the database on INSERT
 *
 * @property Task[] $tasks All tasks that belong to this category (via pivot table)
 */
class Category extends ActiveRecord
{
    // -------------------------------------------------------------------------
    // ActiveRecord configuration
    // -------------------------------------------------------------------------

    /**
     * Returns the name of the database table associated with this model.
     *
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%categories}}';
    }

    // -------------------------------------------------------------------------
    // Validation rules
    // -------------------------------------------------------------------------

    /**
     * Defines the validation rules for category fields.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            // name is mandatory
            [['name'], 'required'],

            // name must fit in VARCHAR(100)
            [['name'], 'string', 'max' => 100],

            // category names must be unique across the entire table
            [['name'], 'unique'],

            // color must be a valid 6-digit hex code preceded by '#' (e.g. '#1a2b3c')
            [['color'], 'match', 'pattern' => '/^#([A-Fa-f0-9]{6})$/'],

            // color is optional; the DB default '#007bff' applies when omitted
            [['color'], 'default', 'value' => '#007bff'],
        ];
    }

    /**
     * Returns human-readable labels for each attribute.
     * Used in form field labels and table column headers.
     *
     * @return array
     */
    public function attributeLabels(): array
    {
        return [
            'id'         => 'ID',
            'name'       => 'Name',
            'color'      => 'Color',
            'created_at' => 'Created At',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * Returns all Task models that are linked to this category.
     * The relationship goes through the task_categories pivot table.
     *
     * Usage: $category->tasks  → array of Task objects
     *
     * @return ActiveQuery
     */
    public function getTasks(): ActiveQuery
    {
        return $this->hasMany(Task::class, ['id' => 'task_id'])
                    ->viaTable('{{%task_categories}}', ['category_id' => 'id']);
    }
}
