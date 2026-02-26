<?php

/**
 * Create Category view.
 *
 * Renders the category creation form by delegating to the shared `_form.php`
 * partial.  On validation failure the controller passes the model back with
 * error messages that the partial renders inline next to each field.
 *
 * Variables passed from CategoryController::actionCreate():
 *   @var yii\web\View          $this  Yii2 View object
 *   @var app\models\Category   $model A new (empty) Category model
 */

use yii\helpers\Html;

$this->title = 'Create Category';
?>

<!-- =========================================================
     Page header
     ========================================================= -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">
        <i class="bi bi-plus-circle me-2"></i><?= Html::encode($this->title) ?>
    </h1>

    <!-- Return to the category list without creating anything -->
    <?= Html::a(
        '<i class="bi bi-arrow-left me-1"></i>Back to list',
        ['index'],
        ['class' => 'btn btn-outline-secondary btn-sm']
    ) ?>
</div>

<!-- =========================================================
     Form card — max-width keeps the form from stretching too wide
     ========================================================= -->
<div class="card shadow-sm" style="max-width:500px;">
    <div class="card-body">
        <?= $this->render('_form', ['model' => $model]) ?>
    </div>
</div>
