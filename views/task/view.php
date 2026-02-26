<?php

/**
 * Task detail view.
 *
 * Shows all fields of a single task in a read-only format using Yii2's
 * DetailView widget, which renders a two-column <table> (label | value).
 *
 * Special rendering:
 *  - Status and priority fields display colour-coded Bootstrap badges
 *  - Categories display coloured badges (one per linked category)
 *  - Overdue due dates are highlighted in red (see index.php for the grid version)
 *  - Action buttons at the bottom: Edit, Change Status, Move to Trash
 *
 * Variables passed from TaskController::actionView():
 *   @var yii\web\View  $this   Yii2 View object
 *   @var app\models\Task $model The task to display
 */

use app\models\Task;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\DetailView;

// Browser tab title = task title
$this->title = $model->title;

/*
 * Pull badge maps from the model's static helpers.
 * Centralising these in the model prevents the same array being defined
 * in every view that needs to display a status or priority badge.
 */
$statusMap   = Task::statusBadgeMap();
$priorityMap = Task::priorityBadgeMap();
?>

<!-- =========================================================
     Page header
     ========================================================= -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">
        <i class="bi bi-card-text me-2"></i><?= Html::encode($this->title) ?>
    </h1>

    <!-- Return to the paginated task list -->
    <?= Html::a(
        '<i class="bi bi-arrow-left me-1"></i>Back to list',
        ['index'],
        ['class' => 'btn btn-outline-secondary btn-sm']
    ) ?>
</div>

<!-- =========================================================
     Detail card — task attributes rendered as a table
     ========================================================= -->
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <?= DetailView::widget([
            'model'   => $model,
            'options' => ['class' => 'table table-bordered detail-view mb-0'],
            'attributes' => [

                // Auto-incrementing primary key
                'id',

                // Task title (plain text)
                'title',

                // Description — ntext format preserves line breaks as <br> tags
                [
                    'attribute' => 'description',
                    'format'    => 'ntext',
                    // Show an em-dash placeholder when description is empty
                    'value'     => $model->description ?: '—',
                ],

                // Status — displayed as a colour-coded Bootstrap badge
                [
                    'attribute' => 'status',
                    'format'    => 'raw',
                    'value'     => (function () use ($model, $statusMap) {
                        [$cls, $label] = $statusMap[$model->status] ?? ['bg-secondary', $model->status];
                        return Html::tag('span', $label, ['class' => "badge {$cls}"]);
                    })(),
                ],

                // Priority — colour-coded badge (low=grey, medium=yellow, high=red)
                [
                    'attribute' => 'priority',
                    'format'    => 'raw',
                    'value'     => (function () use ($model, $priorityMap) {
                        [$cls, $label] = $priorityMap[$model->priority] ?? ['bg-secondary', $model->priority];
                        return Html::tag('span', $label, ['class' => "badge {$cls}"]);
                    })(),
                ],

                // Due date — plain date string; no special formatting needed here
                [
                    'attribute' => 'due_date',
                    'value'     => $model->due_date ?: '—',
                ],

                // Categories — one coloured badge per linked category
                [
                    'label'  => 'Categories',
                    'format' => 'raw',
                    'value'  => (function () use ($model) {
                        if (empty($model->categories)) {
                            return '<span class="text-muted">None</span>';
                        }
                        $html = '';
                        foreach ($model->categories as $c) {
                            $html .= Html::tag('span', Html::encode($c->name), [
                                'class' => 'badge me-1',
                                'style' => "background-color:{$c->color};",
                            ]);
                        }
                        return $html;
                    })(),
                ],

                // Timestamps set/updated automatically by the database
                'created_at',
                'updated_at',
            ],
        ]) ?>
    </div>
</div>

<!-- =========================================================
     Action buttons
     ========================================================= -->
<div class="d-flex gap-2 flex-wrap">

    <!-- Open the edit form for this task -->
    <?= Html::a(
        '<i class="bi bi-pencil me-1"></i>Update',
        ['update', 'id' => $model->id],
        ['class' => 'btn btn-primary']
    ) ?>

    <!--
        Change Status — advances the status through the cycle
        (pending → in_progress → completed → pending).
        Uses data-method="post" so Yii2's yii.js submits it as a POST form
        after the confirmation dialog is accepted.
    -->
    <?= Html::a(
        '<i class="bi bi-arrow-repeat me-1"></i>Change Status',
        ['change-status', 'id' => $model->id],
        [
            'class'        => 'btn btn-warning',
            'data-confirm' => 'Change the status of this task?',
            'data-method'  => 'post',
        ]
    ) ?>

    <!--
        Delete (soft-delete) — moves the task to the Trash.
        The task is NOT permanently removed and can be restored from /task/trash.
    -->
    <?= Html::a(
        '<i class="bi bi-trash me-1"></i>Move to Trash',
        ['delete', 'id' => $model->id],
        [
            'class'        => 'btn btn-danger',
            'data-confirm' => 'Move this task to the Trash?  You can restore it later.',
            'data-method'  => 'post',
        ]
    ) ?>

</div>
