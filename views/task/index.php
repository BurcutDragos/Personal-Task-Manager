<?php

/**
 * Task list view — the main landing page of the application.
 *
 * Features rendered on this page:
 *  - Search / filter form (title, status, priority, category, due date)
 *  - GridView table with sortable columns and pagination
 *  - Colour-coded status and priority badges
 *  - Category badges with the category's custom colour
 *  - Per-row action buttons: view, edit, Ajax status cycle, soft-delete
 *  - Header toolbar: Create Task, Export CSV, Trash, Categories links
 *  - Inline JavaScript for the Ajax "change status" action (no page reload)
 *
 * Variables passed from TaskController::actionIndex():
 *   @var yii\web\View           $this         Yii2 View object
 *   @var yii\data\ActiveDataProvider $dataProvider Paginated/sorted task query
 *   @var app\models\Category[]  $categories   All categories for the filter drop-down
 */

use app\models\Task;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Tasks';
?>

<!-- =========================================================
     Page header — title + action buttons
     ========================================================= -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">
        <i class="bi bi-list-task me-2"></i><?= Html::encode($this->title) ?>
    </h1>

    <div class="d-flex gap-1 flex-wrap">
        <!-- Primary action: open the Create Task form -->
        <?= Html::a(
            '<i class="bi bi-plus-lg me-1"></i>Create Task',
            ['create'],
            ['class' => 'btn btn-success']
        ) ?>

        <!--
            Export to CSV: passes the current filter parameters so the exported
            file matches exactly what is visible in the table below.
        -->
        <?= Html::a(
            '<i class="bi bi-download me-1"></i>Export CSV',
            array_merge(['export'], Yii::$app->request->get()),
            ['class' => 'btn btn-outline-primary']
        ) ?>

        <!--
            Trash link: shows soft-deleted tasks so they can be restored or
            permanently removed.
        -->
        <?= Html::a(
            '<i class="bi bi-trash me-1"></i>Trash',
            ['trash'],
            ['class' => 'btn btn-outline-warning']
        ) ?>

        <!-- Quick link to category management -->
        <?= Html::a(
            '<i class="bi bi-tags me-1"></i>Categories',
            ['category/index'],
            ['class' => 'btn btn-outline-secondary']
        ) ?>
    </div>
</div>

<!-- =========================================================
     Search and filter form
     A plain HTML GET form — filters are passed as query-string
     parameters so the URL is bookmarkable and shareable.
     ========================================================= -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="get" action="<?= Url::to(['task/index']) ?>">
            <div class="row g-2 align-items-end">

                <!-- Free-text search — matches task title and description -->
                <div class="col-md-3">
                    <label class="form-label small mb-1">Search</label>
                    <input
                        type="text"
                        name="search"
                        class="form-control form-control-sm"
                        placeholder="Title or description…"
                        value="<?= Html::encode(Yii::$app->request->get('search', '')) ?>"
                    >
                </div>

                <!-- Status filter drop-down — empty value = all statuses -->
                <div class="col-md-2">
                    <label class="form-label small mb-1">Status</label>
                    <?= Html::dropDownList('status', Yii::$app->request->get('status'), [
                        ''            => 'All statuses',
                        'pending'     => 'Pending',
                        'in_progress' => 'In Progress',
                        'completed'   => 'Completed',
                    ], ['class' => 'form-select form-select-sm']) ?>
                </div>

                <!-- Priority filter drop-down -->
                <div class="col-md-2">
                    <label class="form-label small mb-1">Priority</label>
                    <?= Html::dropDownList('priority', Yii::$app->request->get('priority'), [
                        ''       => 'All priorities',
                        'low'    => 'Low',
                        'medium' => 'Medium',
                        'high'   => 'High',
                    ], ['class' => 'form-select form-select-sm']) ?>
                </div>

                <!-- Category filter — populated from all existing categories -->
                <div class="col-md-2">
                    <label class="form-label small mb-1">Category</label>
                    <?= Html::dropDownList(
                        'category_id',
                        Yii::$app->request->get('category_id'),
                        array_merge(
                            ['' => 'All categories'],
                            ArrayHelper::map($categories, 'id', 'name')
                        ),
                        ['class' => 'form-select form-select-sm']
                    ) ?>
                </div>

                <!-- Due-date filter — native date picker, matches exact date -->
                <div class="col-md-2">
                    <label class="form-label small mb-1">Due date</label>
                    <input
                        type="date"
                        name="due_date"
                        class="form-control form-control-sm"
                        value="<?= Html::encode(Yii::$app->request->get('due_date', '')) ?>"
                    >
                </div>

                <!-- Submit / clear buttons -->
                <div class="col-md-1 d-flex gap-1">
                    <!-- Apply filters -->
                    <button type="submit" class="btn btn-primary btn-sm w-100" title="Search">
                        <i class="bi bi-search"></i>
                    </button>
                    <!-- Reset all filters by navigating back to the bare index URL -->
                    <a href="<?= Url::to(['task/index']) ?>"
                       class="btn btn-outline-secondary btn-sm"
                       title="Clear filters">
                        <i class="bi bi-x-lg"></i>
                    </a>
                </div>

            </div>
        </form>
    </div>
</div>

<!-- =========================================================
     Task table — rendered by Yii2's GridView widget.
     GridView handles pagination links, sorting headers, and
     the row-level action column automatically.
     ========================================================= -->
<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'tableOptions' => ['class' => 'table table-hover table-bordered align-middle grid-view'],
    'summary'      => '<div class="text-muted small mb-2">Showing <b>{begin}–{end}</b> of <b>{totalCount}</b> tasks</div>',
    'columns'      => [

        // Auto-incrementing row number (not the task ID)
        ['class' => 'yii\grid\SerialColumn'],

        // --- Title column — rendered as a link to the task detail page ---
        [
            'attribute' => 'title',
            'format'    => 'raw',
            'value'     => function (Task $model) {
                // Html::a() generates a safe anchor; Html::encode() prevents XSS
                return Html::a(Html::encode($model->title), ['view', 'id' => $model->id]);
            },
        ],

        // --- Categories column — displays colour-coded badge for each category ---
        [
            'label'         => 'Categories',
            'format'        => 'raw',
            // Sorting by a computed/virtual column (categories) is not supported;
            // enableSorting:false removes the sortable header link for this column.
            'enableSorting' => false,
            'value'    => function (Task $model) {
                $html = '';
                foreach ($model->categories as $c) {
                    // Each category is rendered as a coloured Bootstrap badge
                    $html .= Html::tag('span', Html::encode($c->name), [
                        'class' => 'badge me-1',
                        'style' => "background-color:{$c->color};",
                    ]);
                }
                // Em-dash placeholder when no categories are linked
                return $html ?: '<span class="text-muted">—</span>';
            },
        ],

        // --- Priority column — colour-coded badge (low/medium/high) ---
        [
            'attribute' => 'priority',
            'format'    => 'raw',
            'value'     => function (Task $model) {
                // Fetch colours/labels from the model's centralised badge map
                $map = Task::priorityBadgeMap();
                [$cls, $label] = $map[$model->priority] ?? ['bg-secondary', $model->priority];
                return Html::tag('span', $label, ['class' => "badge {$cls}"]);
            },
        ],

        // --- Status column — badge with a stable CSS class for Ajax updates ---
        [
            'attribute' => 'status',
            'format'    => 'raw',
            'value'     => function (Task $model) {
                $map = Task::statusBadgeMap();
                [$cls, $label] = $map[$model->status] ?? ['bg-secondary', $model->status];
                /*
                 * The 'status-badge' class is targeted by the Ajax JS block
                 * at the bottom of this file so the badge text and colour can
                 * be updated in-place after a status change without a reload.
                 */
                return Html::tag('span', $label, ['class' => "badge status-badge {$cls}"]);
            },
        ],

        // --- Due date column — overdue dates highlighted in red ---
        [
            'attribute' => 'due_date',
            'format'    => 'raw',
            'value'     => function (Task $model) {
                if (!$model->due_date) {
                    return '<span class="text-muted">—</span>';
                }
                // Highlight past due dates for non-completed tasks
                $overdue = $model->status !== Task::STATUS_COMPLETED
                    && $model->due_date < date('Y-m-d');
                $cls = $overdue ? 'text-danger fw-semibold' : '';
                return Html::tag('span', Html::encode($model->due_date), ['class' => $cls]);
            },
        ],

        // --- Action column — per-row buttons ---
        [
            'class'    => 'yii\grid\ActionColumn',
            /*
             * Template slots: {view} and {update} use the built-in defaults.
             * {change-status} and {delete} are customised in the 'buttons' array.
             */
            'template' => '{view} {update} {change-status} {delete}',
            'headerOptions' => ['style' => 'width:150px'],
            'buttons'  => [

                /**
                 * "Change Status" button — cycles through pending → in_progress → completed.
                 *
                 * The button uses an Ajax POST so the page does not reload.
                 * The 'btn-change-status' class is the hook for the JavaScript
                 * handler defined in the $this->registerJs() block below.
                 * 'data-url' holds the action URL; the JS reads it on click.
                 */
                'change-status' => function ($url, Task $model) {
                    // Determine what the NEXT status label and icon should look like
                    $nextLabels = [
                        Task::STATUS_PENDING     => ['In Progress', 'bi-arrow-right-circle',     'btn-outline-primary'],
                        Task::STATUS_IN_PROGRESS => ['Complete',    'bi-check-circle',            'btn-outline-success'],
                        Task::STATUS_COMPLETED   => ['Reset',       'bi-arrow-counterclockwise',  'btn-outline-secondary'],
                    ];
                    [$title, $icon, $cls] = $nextLabels[$model->status]
                        ?? ['Change Status', 'bi-arrow-repeat', 'btn-outline-warning'];

                    return Html::a(
                        "<i class=\"bi {$icon}\"></i>",
                        '#', // href="#" — actual request is made via Ajax in JS
                        [
                            'title'            => $title,
                            /*
                             * Two classes are set:
                             *   btn btn-sm {$cls} — Bootstrap button styling
                             *   btn-change-status  — JavaScript hook class
                             */
                            'class'            => "btn btn-sm {$cls} btn-change-status",
                            // The JS handler reads this attribute to build the POST URL
                            'data-url'         => Url::to(['task/change-status', 'id' => $model->id]),
                            // Used by JS to show a native confirmation dialog
                            'data-next-title'  => $title,
                        ]
                    );
                },

                /**
                 * "Delete" button — soft-deletes the task (moves it to Trash).
                 * Uses Yii2's built-in data-method="post" + data-confirm so
                 * yii.js handles the confirmation and form submission.
                 */
                'delete' => function ($url, Task $model) {
                    return Html::a(
                        '<i class="bi bi-trash"></i>',
                        ['task/delete', 'id' => $model->id],
                        [
                            'title'        => 'Move to Trash',
                            'class'        => 'btn btn-sm btn-outline-danger',
                            'data-confirm' => 'Move this task to the Trash?',
                            'data-method'  => 'post',
                        ]
                    );
                },
            ],
            // Default styling for the built-in {view} and {update} buttons
            'buttonOptions' => ['class' => 'btn btn-sm btn-outline-secondary me-1'],
        ],

    ],
]) ?>

<?php
/*
 * Ajax "Change Status" JavaScript
 * --------------------------------
 * This block is registered at the end of the <body> (via POS_END) so that
 * jQuery, Bootstrap, and Yii2's yii.js are all loaded before this runs.
 *
 * How it works:
 *  1. The user clicks a .btn-change-status button.
 *  2. A native confirm() dialog asks for confirmation.
 *  3. $.post() sends the request to the action URL with the CSRF token.
 *  4. The server (TaskController::actionChangeStatus) returns a JSON object.
 *  5. The status badge text/class in the same table row is updated in place.
 *  6. The button's icon, class, tooltip, and data-url are updated to reflect
 *     the new "next" state — ready for the next click.
 */
$this->registerJs(<<<JS
$(document).on('click', '.btn-change-status', function (e) {
    e.preventDefault(); // Prevent the href="#" from changing the URL

    var \$btn     = $(this);
    var url       = \$btn.data('url');
    var nextTitle = \$btn.data('next-title');

    // Native confirmation dialog — user can cancel without making a request
    if (!confirm('Change status to: ' + nextTitle + '?')) {
        return;
    }

    // Retrieve the CSRF token from the meta tag added by Html::csrfMetaTags()
    var csrfToken = \$('meta[name="csrf-token"]').attr('content');

    // POST the request to the server
    $.post(url, {_csrf: csrfToken}, function (response) {
        if (!response.success) {
            alert('Failed to update status. Please try again.');
            return;
        }

        // ---- Update the status badge in the same row ----
        var \$row = \$btn.closest('tr');
        \$row.find('.status-badge')
            .text(response.label)
            .attr('class', 'badge status-badge ' + response.badgeClass);

        // ---- Update the button to show the next available transition ----
        // Icon
        \$btn.find('i').attr('class', 'bi ' + response.nextIcon);

        // Bootstrap colour class: strip old btn-outline-* and add new one
        \$btn.removeClass(function (idx, css) {
            return (css.match(/(^|\s)btn-outline-\S+/g) || []).join(' ');
        }).addClass(response.btnClass);

        // Tooltip and confirmation text for the subsequent click
        // (data-url is unchanged — the same URL handles all status transitions)
        \$btn.attr('title', response.nextTitle)
             .attr('data-next-title', response.nextTitle);

    }, 'json').fail(function () {
        alert('An error occurred while changing the status.');
    });
});
JS, \yii\web\View::POS_END);
?>
