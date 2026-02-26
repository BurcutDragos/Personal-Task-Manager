<?php

/**
 * Trash view — lists all soft-deleted tasks.
 *
 * Soft-deleted tasks have a non-NULL `deleted_at` timestamp.  They are hidden
 * from the normal task list but are kept in the database so the user can
 * either restore them or permanently remove them from here.
 *
 * Features on this page:
 *  - GridView showing only deleted tasks, sorted by deletion date (newest first)
 *  - "Restore" button (POST) — clears deleted_at and returns the task to the list
 *  - "Delete Forever" button (POST + confirmation) — permanently removes the row
 *  - Empty state message when the Trash is empty
 *
 * Variables passed from TaskController::actionTrash():
 *   @var yii\web\View                $this         Yii2 View object
 *   @var yii\data\ActiveDataProvider $dataProvider Paginated soft-deleted tasks
 */

use yii\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Trash';
?>

<!-- =========================================================
     Page header
     ========================================================= -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">
        <i class="bi bi-trash me-2 text-warning"></i><?= Html::encode($this->title) ?>
    </h1>

    <!-- Back to the active task list -->
    <?= Html::a(
        '<i class="bi bi-arrow-left me-1"></i>Back to Tasks',
        ['index'],
        ['class' => 'btn btn-outline-secondary btn-sm']
    ) ?>
</div>

<!-- Explanatory note so the user understands what this page is for -->
<div class="alert alert-warning d-flex align-items-center" role="alert">
    <i class="bi bi-info-circle me-2"></i>
    Tasks shown here have been soft-deleted.  You can <strong>restore</strong> them to make
    them active again, or <strong>delete forever</strong> to remove them permanently.
</div>

<!-- =========================================================
     Trash table
     ========================================================= -->
<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'tableOptions' => ['class' => 'table table-hover table-bordered align-middle'],
    'summary'      => '<div class="text-muted small mb-2">Showing <b>{begin}–{end}</b> of <b>{totalCount}</b> deleted task(s)</div>',

    /*
     * Shown when there are no deleted tasks.
     * A friendly empty-state message is better than a bare "No results found."
     */
    'emptyText'    => '<div class="text-center text-muted py-4">
                           <i class="bi bi-trash display-4 d-block mb-2"></i>
                           The Trash is empty — no deleted tasks here.
                       </div>',

    'columns' => [

        // Row counter (not the task ID)
        ['class' => 'yii\grid\SerialColumn'],

        // --- Task title ---
        [
            'attribute' => 'title',
            'format'    => 'text',
        ],

        // --- Status badge — same colours as the main list ---
        [
            'attribute' => 'status',
            'format'    => 'raw',
            'value'     => function ($model) {
                $map = \app\models\Task::statusBadgeMap();
                [$cls, $label] = $map[$model->status] ?? ['bg-secondary', $model->status];
                return Html::tag('span', $label, ['class' => "badge {$cls}"]);
            },
        ],

        // --- Deletion timestamp — when was the task moved to Trash ---
        [
            'attribute' => 'deleted_at',
            'label'     => 'Deleted At',
            'format'    => 'datetime',  // Yii2 formats as a readable date+time string
        ],

        // --- Action column with Restore and Delete Forever buttons ---
        [
            'class'    => 'yii\grid\ActionColumn',
            // Suppress the built-in {view}/{update}/{delete} buttons entirely;
            // we define our own {restore} and {force-delete} buttons below.
            'template' => '{restore} {force-delete}',
            'headerOptions' => ['style' => 'width:180px'],
            'buttons'  => [

                /**
                 * Restore button — clears deleted_at, making the task active again.
                 * Uses data-method="post" so Yii2's yii.js submits it as a POST form.
                 */
                'restore' => function ($url, $model) {
                    return Html::a(
                        '<i class="bi bi-arrow-counterclockwise me-1"></i>Restore',
                        Url::to(['task/restore', 'id' => $model->id]),
                        [
                            'title'       => 'Restore this task',
                            'class'       => 'btn btn-sm btn-success me-1',
                            'data-method' => 'post',
                        ]
                    );
                },

                /**
                 * Delete Forever button — permanently removes the row from the DB.
                 * A confirmation dialog is shown before the POST is sent.
                 */
                'force-delete' => function ($url, $model) {
                    return Html::a(
                        '<i class="bi bi-trash-fill me-1"></i>Delete Forever',
                        Url::to(['task/force-delete', 'id' => $model->id]),
                        [
                            'title'        => 'Permanently delete — cannot be undone',
                            'class'        => 'btn btn-sm btn-danger',
                            'data-confirm' => 'Permanently delete this task? This CANNOT be undone.',
                            'data-method'  => 'post',
                        ]
                    );
                },

            ],
        ],

    ],
]) ?>
