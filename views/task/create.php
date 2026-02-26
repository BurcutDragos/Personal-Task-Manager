<?php

/**
 * Create Task view.
 *
 * Renders a blank task creation form by including the shared `_form.php` partial.
 * The form handles both the GET display and the POST submission — on validation
 * failure the controller re-renders this view with the model's error messages
 * so they appear inline next to the relevant fields.
 *
 * Variables passed from TaskController::actionCreate():
 *   @var yii\web\View           $this       Yii2 View object
 *   @var app\models\Task        $model      A new (empty) Task model with defaults
 *   @var app\models\Category[]  $categories All available categories for the checkboxes
 */

use yii\helpers\Html;

// Set the browser tab title and the <h1> heading
$this->title = 'Create Task';
?>

<!-- =========================================================
     Page header
     ========================================================= -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">
        <i class="bi bi-plus-circle me-2"></i><?= Html::encode($this->title) ?>
    </h1>

    <!-- Navigation back to the task list without creating anything -->
    <?= Html::a(
        '<i class="bi bi-arrow-left me-1"></i>Back to list',
        ['index'],
        ['class' => 'btn btn-outline-secondary btn-sm']
    ) ?>
</div>

<!-- =========================================================
     Form card
     The actual form fields are in the _form.php partial so they
     can be reused identically by the Update view.
     ========================================================= -->
<div class="card shadow-sm">
    <div class="card-body">
        <?= $this->render('_form', [
            'model'      => $model,
            'categories' => $categories,
            // No categories pre-selected for a brand-new task
            'selected'   => [],
        ]) ?>
    </div>
</div>
