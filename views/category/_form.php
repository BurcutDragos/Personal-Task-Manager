<?php

/**
 * Shared category form partial — reused by both Create and Update views.
 *
 * This file contains only the form fields (no page-level headings or card
 * wrappers).  The parent view provides the surrounding layout.
 *
 * Fields:
 *  - name  : text input for the category label (max 100 chars, must be unique)
 *  - color : native colour picker (<input type="color">)
 *            defaults to #007bff (Bootstrap blue) if left unchanged
 *
 * Yii2's ActiveForm automatically:
 *  - Adds the CSRF token as a hidden field
 *  - Renders inline validation errors using the model's rules()
 *  - Enables client-side validation via JavaScript
 *
 * Variables expected by this partial:
 *   @var yii\web\View         $this  Yii2 View object
 *   @var app\models\Category  $model Category model (new or existing)
 */

use yii\helpers\Html;
use yii\widgets\ActiveForm;
?>

<?php
/*
 * Open the <form> tag.  Yii2 will POST to the current controller/action URL.
 * For actionCreate() that is POST /category/create.
 * For actionUpdate() that is POST /category/update?id=N.
 */
$form = ActiveForm::begin();
?>

<!--
    Name field — the human-readable category label (e.g. "Work", "Urgent").
    autofocus places the cursor here immediately when the page loads.
    maxlength:true reads the 100-char limit from the model's 'string' rule.
    placeholder gives new users an example of what to type.
-->
<?= $form->field($model, 'name')->textInput([
    'maxlength'   => true,
    'autofocus'   => true,
    'class'       => 'form-control',
    'placeholder' => 'e.g. Work, Personal, Urgent…',
]) ?>

<!--
    Colour picker — uses the browser's native colour picker UI.
    form-control-color is Bootstrap 5's utility class for styled <input type="color">.
    The fixed width prevents the picker from stretching to the full column width.
    A hint below the field explains what the colour is used for.
-->
<?= $form->field($model, 'color')
    ->input('color', ['class' => 'form-control form-control-color', 'style' => 'width:80px;'])
    ->hint('Pick a colour that will be used as the category badge background.') ?>

<!-- Submit button — label adapts based on whether we're creating or editing -->
<div class="mt-3">
    <?= Html::submitButton(
        $model->isNewRecord
            ? '<i class="bi bi-plus-lg me-1"></i>Create Category'
            : '<i class="bi bi-save me-1"></i>Save Changes',
        ['class' => 'btn btn-primary']
    ) ?>
</div>

<?php ActiveForm::end(); ?>
