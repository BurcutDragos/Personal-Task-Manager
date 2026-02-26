<?php

/**
 * Main application layout — wraps every page with the common HTML skeleton.
 *
 * Responsibilities:
 *  - Outputs the <html>/<head>/<body> boilerplate
 *  - Loads Bootstrap 5 and Bootstrap Icons from CDN (no local assets needed)
 *  - Loads jQuery from CDN *before* $this->endBody() so that Yii2's yii.js
 *    (which depends on jQuery) finds it on the page
 *  - Renders the navigation bar with links to Tasks, Categories, and Trash
 *  - Displays any session flash messages (success / error / warning / info)
 *  - Yields the per-page content via the $content variable
 *
 * Variables injected by Yii2 automatically:
 *   @var yii\web\View $this    The current View object
 *   @var string       $content HTML produced by the child view that was rendered
 */

use yii\helpers\Html;
use yii\helpers\Url;

// Register the <title> that child views can override via $this->title
$this->beginPage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF meta tags — required for Yii2's Ajax CSRF protection -->
    <?= Html::csrfMetaTags() ?>

    <title><?= Html::encode($this->title ?? 'Personal Task Manager') ?> | Personal Task Manager</title>

    <!-- Bootstrap 5 CSS from CDN — provides the grid, utilities, and components -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

    <!-- Bootstrap Icons CDN — icon font used throughout the UI -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <!-- Minimal global style overrides -->
    <style>
        /* Light grey page background so white cards stand out */
        body { background-color: #f8f9fa; }

        /* Make the brand name bold and slightly spaced */
        .navbar-brand { font-weight: 700; letter-spacing: .5px; }

        /* Slightly smaller badges to avoid overwhelming the table cells */
        .badge { font-size: .8em; }

        /* Ensure GridView cell content is vertically centred */
        table.grid-view td, table.grid-view th { vertical-align: middle; }
    </style>

    <?php
    /*
     * $this->head() outputs anything that child views or assets have registered
     * for the <head> section — e.g. inline CSS, link tags added via
     * $this->registerCssFile() in a view.
     */
    $this->head();
    ?>
</head>
<body>
<?php
/*
 * $this->beginBody() fires the EVENT_BEGIN_BODY event so that Yii2's asset
 * bundles can inject scripts that need to be at the very top of <body>.
 */
$this->beginBody();
?>

<!-- =========================================================
     Top navigation bar
     ========================================================= -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4 shadow-sm">
    <div class="container">

        <!-- Brand / logo — clicking takes the user back to the task list -->
        <a class="navbar-brand" href="<?= Url::to(['task/index']) ?>">
            <i class="bi bi-check2-square me-2"></i>Personal Task Manager
        </a>

        <!-- Hamburger button shown on small screens (Bootstrap collapse) -->
        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#navMenu"
                aria-controls="navMenu" aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Collapsible nav links -->
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav ms-auto">

                <!-- Tasks list -->
                <li class="nav-item">
                    <a class="nav-link" href="<?= Url::to(['task/index']) ?>">
                        <i class="bi bi-list-task me-1"></i>Tasks
                    </a>
                </li>

                <!-- Category management -->
                <li class="nav-item">
                    <a class="nav-link" href="<?= Url::to(['category/index']) ?>">
                        <i class="bi bi-tags me-1"></i>Categories
                    </a>
                </li>

                <!-- Trash / soft-deleted tasks -->
                <li class="nav-item">
                    <a class="nav-link text-warning" href="<?= Url::to(['task/trash']) ?>">
                        <i class="bi bi-trash me-1"></i>Trash
                    </a>
                </li>

            </ul>
        </div>
    </div>
</nav>

<!-- =========================================================
     Main content area
     ========================================================= -->
<div class="container pb-5">

    <?php
    /*
     * Flash message display.
     *
     * Controllers set flashes via Yii::$app->session->setFlash('success', '...').
     * We loop over all four severity levels and render the appropriate Bootstrap
     * alert component.  The alert is dismissible so the user can close it.
     *
     * Mapping: 'error' → Bootstrap's 'danger' class (Bootstrap uses 'danger',
     * Yii conventionally uses 'error').
     */
    foreach (['success', 'error', 'warning', 'info'] as $type):
        if (Yii::$app->session->hasFlash($type)):
            // Pick the correct Bootstrap icon for each severity level
            $icon = match ($type) {
                'success' => 'check-circle',
                'error'   => 'exclamation-triangle',
                'warning' => 'exclamation-circle',
                default   => 'info-circle',
            };
            // Bootstrap alert class: 'error' must become 'danger'
            $alertClass = ($type === 'error') ? 'danger' : $type;
    ?>
        <div class="alert alert-<?= $alertClass ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-<?= $icon ?> me-2"></i>
            <?= Html::encode(Yii::$app->session->getFlash($type)) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php
        endif;
    endforeach;
    ?>

    <?= $content ?>
</div>

<!-- =========================================================
     Scripts (bottom of body for best page-load performance)
     ========================================================= -->

<!--
    jQuery MUST be loaded before $this->endBody() below.
    Yii2 outputs yii.js at endBody() and yii.js requires jQuery to already
    be defined.  Loading jQuery from CDN here satisfies that requirement
    without needing a local bower-asset/jquery installation.
-->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>

<!-- Bootstrap 5 JS bundle (includes Popper.js for dropdowns/tooltips) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<?php
/*
 * $this->endBody() fires the EVENT_END_BODY event.  Yii2 outputs yii.js here
 * (which handles data-method, data-confirm, PJAX, etc.) along with any other
 * scripts registered by child views via $this->registerJs() or asset bundles.
 */
$this->endBody();
?>
</body>
</html>
<?php
/*
 * $this->endPage() finalises the page lifecycle and flushes any remaining
 * output buffers set up in beginPage().
 */
$this->endPage();
?>
