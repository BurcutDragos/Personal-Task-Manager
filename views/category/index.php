<?php

/**
 * Category list view.
 *
 * Displays all categories in a sortable, paginated GridView table.
 * Each row shows the category name (as a coloured badge), a colour swatch
 * with the hex code, the creation timestamp, and action buttons.
 *
 * Features:
 *  - Name column: coloured badge styled with the category's own colour
 *  - Colour column: small filled square preview + the raw hex code
 *  - ActionColumn with Update and Delete buttons
 *
 * Variables passed from CategoryController::actionIndex():
 *   @var yii\web\View                $this         Yii2 View object
 *   @var yii\data\ActiveDataProvider $dataProvider Paginated category list
 */

use yii\grid\GridView;
use yii\helpers\Html;

$this->title = 'Categories';
?>

<!-- =========================================================
     Page header — title + action buttons
     ========================================================= -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">
        <i class="bi bi-tags me-2"></i><?= Html::encode($this->title) ?>
    </h1>
    <div>
        <!-- Open the create-category form -->
        <?= Html::a(
            '<i class="bi bi-plus-lg me-1"></i>Create Category',
            ['create'],
            ['class' => 'btn btn-success']
        ) ?>

        <!-- Cross-navigation back to the task list -->
        <?= Html::a(
            '<i class="bi bi-list-task me-1"></i>Tasks',
            ['task/index'],
            ['class' => 'btn btn-outline-secondary ms-1']
        ) ?>
    </div>
</div>

<!-- =========================================================
     Categories table
     ========================================================= -->
<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'tableOptions' => ['class' => 'table table-hover table-bordered align-middle'],
    'summary'      => '<div class="text-muted small mb-2">Showing <b>{begin}–{end}</b> of <b>{totalCount}</b> categories</div>',
    'columns'      => [

        // Auto-incrementing row counter
        ['class' => 'yii\grid\SerialColumn'],

        // --- Name column — renders the name as a coloured badge ---
        [
            'attribute' => 'name',
            'format'    => 'raw',
            'value'     => function ($model) {
                /*
                 * The badge background is set to the category's own colour value.
                 * fs-6 makes the font size slightly larger so the name is readable
                 * inside the badge.
                 */
                return Html::tag('span', Html::encode($model->name), [
                    'class' => 'badge fs-6',
                    'style' => "background-color:{$model->color};",
                ]);
            },
        ],

        // --- Colour column — small filled square swatch + hex code text ---
        [
            'attribute' => 'color',
            'format'    => 'raw',
            'value'     => function ($model) {
                /*
                 * Two elements are rendered side by side:
                 *   1. An empty <span> styled as a small coloured square (the swatch).
                 *   2. The hex code string (e.g. "#007bff") as plain text.
                 *
                 * The inner content of the span is intentionally empty ('') — the
                 * colour itself is the visual, not text inside it.
                 */
                $swatch = Html::tag('span', '', [
                    'style' => "display:inline-block;width:18px;height:18px;"
                             . "background:{$model->color};"
                             . "border-radius:3px;vertical-align:middle;margin-right:6px;",
                ]);
                return $swatch . Html::encode($model->color);
            },
        ],

        // --- Creation date — displayed as-is (stored as DATETIME by MySQL) ---
        'created_at',

        // --- Action column with Update and Delete buttons ---
        [
            'class'         => 'yii\grid\ActionColumn',
            // Only show update and delete — there's no standalone "view" page for categories
            'template'      => '{update} {delete}',
            'headerOptions' => ['style' => 'width:100px'],
            'buttonOptions' => ['class' => 'btn btn-sm btn-outline-secondary me-1'],
        ],

    ],
]) ?>
