<?php

/**
 * Shared task form partial — used by both Create and Update views.
 *
 * This partial is intentionally kept free of page-level HTML (headings, card
 * wrappers, back-links).  The parent view (create.php or update.php) provides
 * that chrome so this file contains only the form fields.
 *
 * Yii2's ActiveForm widget generates:
 *  - The <form> tag with the correct action URL, method, and CSRF token
 *  - <input>/<select>/<textarea> elements bound to model attributes
 *  - Inline client-side and server-side validation error messages
 *
 * Variables expected by this partial (passed via $this->render()):
 *   @var yii\web\View           $this       Yii2 View object
 *   @var app\models\Task        $model      Task model (new or existing)
 *   @var app\models\Category[]  $categories All available categories (for checkboxes)
 *   @var int[]                  $selected   IDs of categories that should be pre-checked
 */

use app\models\Task;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
?>

<?php
/*
 * ActiveForm::begin() opens the <form> tag and registers Yii2's client-side
 * validation JavaScript.  ActiveForm::end() closes the tag and flushes
 * any remaining validation logic.
 */
$form = ActiveForm::begin(['options' => ['class' => 'needs-validation']]);
?>

<!--
    Title field — the only required attribute.
    maxlength:true reads the VARCHAR(255) constraint from the DB automatically.
    autofocus places the cursor here when the page loads.
-->
<?= $form->field($model, 'title')
    ->textInput(['maxlength' => true, 'autofocus' => true, 'class' => 'form-control']) ?>

<!--
    Description field — optional free-text area.
    4 rows gives a comfortable editing height without dominating the form.
-->
<?= $form->field($model, 'description')
    ->textarea(['rows' => 4, 'class' => 'form-control']) ?>

<!-- Status and Priority sit side-by-side on medium+ screens -->
<div class="row">
    <div class="col-md-6">
        <!--
            Status drop-down — the three allowed ENUM values.
            The model's init() pre-selects 'pending' for new records.
        -->
        <?= $form->field($model, 'status')->dropDownList([
            Task::STATUS_PENDING     => 'Pending',
            Task::STATUS_IN_PROGRESS => 'In Progress',
            Task::STATUS_COMPLETED   => 'Completed',
        ], ['class' => 'form-select']) ?>
    </div>
    <div class="col-md-6">
        <!--
            Priority drop-down — the three allowed ENUM values.
            The model's init() pre-selects 'medium' for new records.
        -->
        <?= $form->field($model, 'priority')->dropDownList([
            Task::PRIORITY_LOW    => 'Low',
            Task::PRIORITY_MEDIUM => 'Medium',
            Task::PRIORITY_HIGH   => 'High',
        ], ['class' => 'form-select']) ?>
    </div>
</div>

<!--
    Due Date — native HTML date picker.
    The 'date' input type lets the browser render a calendar picker.
    Yii2 validates the value against the 'php:Y-m-d' format rule defined
    in Task::rules().
-->
<?= $form->field($model, 'due_date')->input('date', ['class' => 'form-control']) ?>

<!--
    Category multi-select — rendered as a grid of checkboxes.

    Categories are NOT a standard ActiveRecord attribute on the Task model; they
    live in the task_categories pivot table.  We therefore use a plain HTML
    checkbox array (name="category_ids[]") instead of an ActiveForm field.
    The controller reads Yii::$app->request->post('category_ids', []) and
    passes the IDs to $model->setCategoryIds(), which queues them for afterSave().
-->
<div class="mb-3">
    <label class="form-label fw-semibold">Categories</label>

    <?php if (empty($categories)): ?>
        <!-- Show a helpful prompt when no categories have been created yet -->
        <p class="text-muted small">
            No categories yet.
            <a href="<?= Url::to(['category/create']) ?>">Create one</a>.
        </p>
    <?php else: ?>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($categories as $c): ?>
                <div class="form-check form-check-inline">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        name="category_ids[]"
                        id="cat_<?= $c->id ?>"
                        value="<?= $c->id ?>"
                        <?= in_array($c->id, $selected ?? [], true) ? 'checked' : '' ?>
                    >
                    <!--
                        The label wraps a coloured badge so clicking the badge
                        toggles the checkbox — better touch target than clicking
                        a tiny checkbox directly.
                    -->
                    <label class="form-check-label" for="cat_<?= $c->id ?>">
                        <span class="badge" style="background-color:<?= Html::encode($c->color) ?>;">
                            <?= Html::encode($c->name) ?>
                        </span>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Submit button — label adapts based on whether we're creating or editing -->
<div class="mt-3">
    <?= Html::submitButton(
        $model->isNewRecord
            ? '<i class="bi bi-plus-lg me-1"></i>Create Task'
            : '<i class="bi bi-save me-1"></i>Save Changes',
        ['class' => 'btn btn-primary']
    ) ?>
</div>

<?php ActiveForm::end(); ?>
