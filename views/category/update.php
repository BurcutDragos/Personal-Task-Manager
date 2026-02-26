<?php

/**
 * Update Category view.
 *
 * Renders a pre-filled category editing form using the shared `_form.php` partial.
 * The existing attribute values are automatically populated by Yii2's ActiveForm
 * because the model is loaded from the database before being passed here.
 *
 * Variables passed from CategoryController::actionUpdate():
 *   @var yii\web\View         $this  Yii2 View object
 *   @var app\models\Category  $model The existing Category model loaded from the DB
 */

use yii\helpers\Html;

// Include the category name in the page title so the user knows what they're editing
$this->title = 'Update Category: ' . $model->name;
?>

<!-- =========================================================
     Page header
     ========================================================= -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">
        <i class="bi bi-pencil me-2"></i><?= Html::encode($this->title) ?>
    </h1>

    <!-- Return to the category list without saving changes -->
    <?= Html::a(
        '<i class="bi bi-arrow-left me-1"></i>Back to list',
        ['index'],
        ['class' => 'btn btn-outline-secondary btn-sm']
    ) ?>
</div>

<!-- =========================================================
     Form card
     ========================================================= -->
<div class="card shadow-sm" style="max-width:500px;">
    <div class="card-body">
        <?= $this->render('_form', ['model' => $model]) ?>
    </div>
</div>
