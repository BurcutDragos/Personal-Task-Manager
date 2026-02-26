<?php

/**
 * Task ActiveRecord model.
 *
 * Represents a single task in the Personal Task Manager application.
 * Extends Yii2's ActiveRecord, which provides automatic mapping between
 * this PHP class and the `tasks` database table.
 *
 * Features implemented in this model:
 * - Validation rules for all user-supplied fields
 * - Many-to-many relationship with Category via the task_categories pivot table
 * - Category assignment via setCategoryIds() / afterSave() hook
 * - Soft-delete: setting deleted_at marks a task as trashed without removing the DB row
 * - find() is overridden so that all standard queries automatically exclude soft-deleted tasks
 */

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\ActiveQuery;

/**
 * Class Task
 *
 * @property int         $id          Auto-incrementing primary key
 * @property string      $title       Short task title (max 255 chars, required)
 * @property string|null $description Longer optional description
 * @property string      $status      One of: 'pending', 'in_progress', 'completed'
 * @property string      $priority    One of: 'low', 'medium', 'high'
 * @property string|null $due_date    Optional deadline in Y-m-d format
 * @property string      $created_at  Timestamp set automatically by the database on INSERT
 * @property string      $updated_at  Timestamp updated automatically by the database on UPDATE
 * @property string|null $deleted_at  NULL = active; timestamp = soft-deleted at that moment
 *
 * @property Category[]     $categories     All categories linked to this task (via pivot table)
 * @property TaskCategory[] $taskCategories All pivot rows linking this task to categories
 */
class Task extends ActiveRecord
{
    // -------------------------------------------------------------------------
    // Status constants — used throughout the app so we never rely on raw strings
    // -------------------------------------------------------------------------

    /** Task has been created but work has not started yet. */
    const STATUS_PENDING = 'pending';

    /** Work on this task is currently in progress. */
    const STATUS_IN_PROGRESS = 'in_progress';

    /** The task has been finished. */
    const STATUS_COMPLETED = 'completed';

    // -------------------------------------------------------------------------
    // Priority constants
    // -------------------------------------------------------------------------

    const PRIORITY_LOW    = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH   = 'high';

    /**
     * Internal cache for pending category ID assignments.
     * Populated by setCategoryIds() and consumed by afterSave().
     *
     * @var int[]|null
     */
    private ?array $_categoryIds = null;

    // -------------------------------------------------------------------------
    // ActiveRecord configuration
    // -------------------------------------------------------------------------

    /**
     * Returns the name of the database table associated with this model.
     * The {{%}} syntax applies the configured table prefix automatically.
     *
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%tasks}}';
    }

    /**
     * Override the default find() factory to automatically exclude soft-deleted tasks.
     *
     * Every time code calls Task::find(), Task::findOne(), Task::findAll(), etc.
     * the returned query will have a WHERE deleted_at IS NULL condition added,
     * so soft-deleted tasks are invisible to the rest of the application.
     *
     * To intentionally query deleted tasks (e.g. for the Trash view), call
     * Task::findWithTrashed() instead.
     *
     * @return ActiveQuery
     */
    public static function find(): ActiveQuery
    {
        // parent::find() builds the base ActiveQuery for the tasks table.
        // We then add our soft-delete filter before returning it.
        return parent::find()->andWhere(['{{%tasks}}.deleted_at' => null]);
    }

    /**
     * Returns a query that includes ALL tasks — both active and soft-deleted.
     * Used by the Trash controller actions (restore, force-delete).
     *
     * @return ActiveQuery
     */
    public static function findWithTrashed(): ActiveQuery
    {
        // Bypass our overridden find() and go straight to the base implementation
        // so no deleted_at filter is added.
        return parent::find();
    }

    /**
     * Sets default field values when a new (unsaved) Task object is created.
     * This ensures the Create form pre-selects sensible defaults in the dropdowns.
     */
    public function init(): void
    {
        parent::init();

        // Only apply defaults to brand-new records (not when loading from the DB)
        if ($this->isNewRecord) {
            $this->status   = self::STATUS_PENDING;
            $this->priority = self::PRIORITY_MEDIUM;
        }
    }

    // -------------------------------------------------------------------------
    // Validation rules
    // -------------------------------------------------------------------------

    /**
     * Defines the validation rules that Yii2 enforces when saving a task.
     *
     * Rules are checked automatically during $model->save() and by ActiveForm
     * on the client side (browser) as well.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            // title is the only truly required field
            [['title'], 'required'],

            // description can be any length of text
            [['description'], 'string'],

            // due_date must be a valid calendar date in Y-m-d format
            [['due_date'], 'date', 'format' => 'php:Y-m-d'],

            // status must be one of the three allowed ENUM values
            ['status', 'in', 'range' => [
                self::STATUS_PENDING,
                self::STATUS_IN_PROGRESS,
                self::STATUS_COMPLETED,
            ]],

            // priority must be one of the three allowed ENUM values
            ['priority', 'in', 'range' => [
                self::PRIORITY_LOW,
                self::PRIORITY_MEDIUM,
                self::PRIORITY_HIGH,
            ]],

            // title has a maximum length matching the VARCHAR(255) column
            [['title'], 'string', 'max' => 255],
        ];
    }

    /**
     * Returns human-readable labels for each model attribute.
     * These labels are displayed in form field labels and GridView column headers.
     *
     * @return array
     */
    public function attributeLabels(): array
    {
        return [
            'id'          => 'ID',
            'title'       => 'Title',
            'description' => 'Description',
            'status'      => 'Status',
            'priority'    => 'Priority',
            'due_date'    => 'Due Date',
            'created_at'  => 'Created At',
            'updated_at'  => 'Updated At',
            'deleted_at'  => 'Deleted At',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * Returns all TaskCategory pivot rows that belong to this task.
     * This is the "has many through" link table side.
     *
     * @return ActiveQuery
     */
    public function getTaskCategories(): ActiveQuery
    {
        return $this->hasMany(TaskCategory::class, ['task_id' => 'id']);
    }

    /**
     * Returns all Category models associated with this task.
     * The relationship goes through the task_categories pivot table.
     *
     * Usage: $task->categories  → array of Category objects
     *
     * @return ActiveQuery
     */
    public function getCategories(): ActiveQuery
    {
        return $this->hasMany(Category::class, ['id' => 'category_id'])
                    ->viaTable('{{%task_categories}}', ['task_id' => 'id']);
    }

    // -------------------------------------------------------------------------
    // Category assignment helpers
    // -------------------------------------------------------------------------

    /**
     * Stores a list of category IDs to be saved when the task is next persisted.
     *
     * Call this before $model->save().  The actual database writes happen in
     * afterSave() to ensure they run inside the same transaction as the task row.
     *
     * @param array $ids Array of integer category IDs (e.g. [1, 3, 5])
     */
    public function setCategoryIds(array $ids): void
    {
        // Cast every value to int to prevent type-related bugs
        $this->_categoryIds = array_map('intval', $ids);
    }

    /**
     * Returns the IDs of all categories currently linked to this task.
     * On the first call the IDs are derived from the loaded `categories` relation
     * and then cached for subsequent calls.
     *
     * @return int[]
     */
    public function getCategoryIds(): array
    {
        if ($this->_categoryIds === null) {
            // Load category IDs from the existing many-to-many relationship
            $this->_categoryIds = array_map(
                fn(Category $c) => $c->id,
                $this->categories
            );
        }

        return $this->_categoryIds;
    }

    /**
     * Yii2 lifecycle hook — called automatically after a successful save().
     *
     * If category IDs were queued via setCategoryIds(), this method deletes the
     * existing pivot rows for this task and inserts fresh ones.  Running this
     * inside afterSave() guarantees it participates in the same DB transaction
     * as the task row itself.
     *
     * @param bool  $insert           TRUE on INSERT (new record), FALSE on UPDATE
     * @param array $changedAttributes Attributes that were changed during this save
     */
    public function afterSave($insert, $changedAttributes): void
    {
        parent::afterSave($insert, $changedAttributes);

        // Only sync categories if setCategoryIds() was called before save()
        if ($this->_categoryIds !== null) {
            // Remove all existing category links for this task
            TaskCategory::deleteAll(['task_id' => $this->id]);

            // Insert a fresh pivot row for each selected category
            foreach ($this->_categoryIds as $categoryId) {
                $pivot              = new TaskCategory();
                $pivot->task_id     = $this->id;
                $pivot->category_id = $categoryId;
                $pivot->save(false); // save(false) skips validation for pivot rows
            }
        }
    }

    // -------------------------------------------------------------------------
    // Soft-delete helpers
    // -------------------------------------------------------------------------

    /**
     * Soft-deletes this task by recording the current timestamp in deleted_at.
     *
     * The task row stays in the database and can be restored later.
     * After calling this method the task will no longer appear in normal listings
     * because Task::find() automatically filters out rows where deleted_at IS NOT NULL.
     *
     * @return bool TRUE if the save succeeded
     */
    public function softDelete(): bool
    {
        $this->deleted_at = date('Y-m-d H:i:s');
        return $this->save(false); // skip validation — we only change deleted_at
    }

    /**
     * Restores a soft-deleted task by clearing the deleted_at timestamp.
     * After calling this method the task will reappear in normal listings.
     *
     * @return bool TRUE if the save succeeded
     */
    public function restore(): bool
    {
        $this->deleted_at = null;
        return $this->save(false);
    }

    // -------------------------------------------------------------------------
    // Static helper maps (reused across controllers and views)
    // -------------------------------------------------------------------------

    /**
     * Returns a map of status values to their Bootstrap badge CSS class and display label.
     * Centralising this here avoids repeating the same array in every view.
     *
     * @return array  Keys = status constant values, values = ['badgeClass', 'label']
     */
    public static function statusBadgeMap(): array
    {
        return [
            self::STATUS_PENDING     => ['bg-secondary', 'Pending'],
            self::STATUS_IN_PROGRESS => ['bg-primary',   'In Progress'],
            self::STATUS_COMPLETED   => ['bg-success',   'Completed'],
        ];
    }

    /**
     * Returns a map of priority values to their Bootstrap badge CSS class and display label.
     *
     * @return array
     */
    public static function priorityBadgeMap(): array
    {
        return [
            self::PRIORITY_LOW    => ['bg-secondary',         'Low'],
            self::PRIORITY_MEDIUM => ['bg-warning text-dark', 'Medium'],
            self::PRIORITY_HIGH   => ['bg-danger',            'High'],
        ];
    }
}
