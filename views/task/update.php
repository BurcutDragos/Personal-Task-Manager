<?php

/**
 * Update Task view.
 *
 * Renders a pre-filled task editing form by including the shared `_form.php` partial.
 * The form fields are populated from the existing task model.  When submitted, the
 * controller validates the input and either saves the changes or re-renders this
 * page with inline validation error messages.
 *
 * Variables passed from TaskController::actionUpdate():
 *   @var yii\web\View           $this       Yii2 View object
 *   @var app\models\Task        $model      The existing Task model loaded from the DB
 *   @var app\models\Category[]  $categories All available categories for the checkboxes
 *   @var int[]                  $selected   IDs of categories currently linked to this task
 */

use yii\helpers\Html;

// Include the task title in the browser tab so users know what they're editing
$this->title = 'Update Task: ' . $model->title;
?>

<!-- =========================================================
     Page header
     ========================================================= -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">
        <i class="bi bi-pencil me-2"></i><?= Html::encode($this->title) ?>
    </h1>

    <div>
        <!-- Quick link to the read-only detail view for this task -->
        <?= Html::a(
            '<i class="bi bi-eye me-1"></i>View',
            ['view', 'id' => $model->id],
            ['class' => 'btn btn-outline-info btn-sm']
        ) ?>

        <!-- Back to the full task list -->
        <?= Html::a(
            '<i class="bi bi-arrow-left me-1"></i>Back to list',
            ['index'],
            ['class' => 'btn btn-outline-secondary btn-sm ms-1']
        ) ?>
    </div>
</div>

<!-- =========================================================
     Form card
     The actual form fields are in the _form.php partial so they
     can be reused identically by the Create view.
     ========================================================= -->
<div class="card shadow-sm">
    <div class="card-body">
        <?= $this->render('_form', [
            'model'      => $model,
            'categories' => $categories,
            // Pass the currently assigned category IDs so checkboxes are pre-checked
            'selected'   => $selected,
        ]) ?>
    </div>
</div>
