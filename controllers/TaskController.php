<?php

/**
 * TaskController — handles all web requests related to tasks.
 *
 * In Yii2's MVC pattern the controller sits between the browser (View) and the
 * data layer (Model).  Each public method named action*() maps to a URL:
 *
 *   actionIndex()       → GET  /task/index        (task list with search/filter)
 *   actionView($id)     → GET  /task/view?id=N    (single task detail)
 *   actionCreate()      → GET/POST /task/create   (create form + submission)
 *   actionUpdate($id)   → GET/POST /task/update?id=N
 *   actionDelete($id)   → POST /task/delete?id=N  (soft-delete)
 *   actionChangeStatus($id) → POST /task/change-status?id=N (Ajax-aware)
 *   actionTrash()       → GET  /task/trash        (soft-deleted tasks)
 *   actionRestore($id)  → POST /task/restore?id=N
 *   actionForceDelete($id) → POST /task/force-delete?id=N  (permanent removal)
 *   actionExport()      → GET  /task/export       (download filtered tasks as CSV)
 */

namespace app\controllers;

use Yii;
use app\models\Task;
use app\models\TaskCategory;
use app\models\Category;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\filters\VerbFilter;

class TaskController extends Controller
{
    // -------------------------------------------------------------------------
    // Filters
    // -------------------------------------------------------------------------

    /**
     * Registers action filters that run before each action method.
     *
     * VerbFilter enforces which HTTP methods are allowed for each action.
     * Write operations (delete, restore, etc.) require POST so that a plain
     * hyperlink or browser pre-fetch can never accidentally modify data.
     *
     * @return array
     */
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class'   => VerbFilter::class,
                'actions' => [
                    'delete'       => ['POST'],
                    'change-status'=> ['POST'],
                    'restore'      => ['POST'],
                    'force-delete' => ['POST'],
                ],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // CRUD actions
    // -------------------------------------------------------------------------

    /**
     * Lists all active (non-deleted) tasks with search, filter, sort and pagination.
     *
     * Query parameters accepted (all optional, all come from the filter form):
     *   search      – keyword matched against title and description (LIKE search)
     *   status      – filter by exact status value
     *   priority    – filter by exact priority value
     *   category_id – filter tasks that belong to a specific category
     *   due_date    – filter by exact due date (Y-m-d)
     *
     * @return string Rendered HTML of the task list page
     */
    public function actionIndex(): string
    {
        // Start with all active tasks and eagerly load their categories.
        // Task::find() automatically excludes soft-deleted rows (deleted_at IS NULL).
        // with('categories') prevents N+1 queries when rendering category badges.
        $query = Task::find()->with('categories');

        // --- Search (title or description contains keyword) ---
        $search = Yii::$app->request->get('search');
        if (!empty($search)) {
            $query->andWhere(['or',
                ['like', '{{%tasks}}.title', $search],
                ['like', '{{%tasks}}.description', $search],
            ]);
        }

        // --- Filter by status ---
        $status = Yii::$app->request->get('status');
        if ($status) {
            $query->andWhere(['{{%tasks}}.status' => $status]);
        }

        // --- Filter by priority ---
        $priority = Yii::$app->request->get('priority');
        if ($priority) {
            $query->andWhere(['{{%tasks}}.priority' => $priority]);
        }

        // --- Filter by category (join the pivot + category tables) ---
        $categoryId = Yii::$app->request->get('category_id');
        if ($categoryId) {
            // joinWith adds the necessary JOIN; andWhere narrows to the chosen category
            $query->joinWith('categories')->andWhere(['{{%categories}}.id' => $categoryId]);
        }

        // --- Filter by exact due date ---
        $dueDate = Yii::$app->request->get('due_date');
        if ($dueDate) {
            $query->andWhere(['{{%tasks}}.due_date' => $dueDate]);
        }

        // --- Sorting and Pagination via ActiveDataProvider ---
        // ActiveDataProvider wraps the query and handles:
        //   • Pagination (splits results across pages, 10 per page)
        //   • Sorting    (reads a 'sort' GET param and adds ORDER BY)
        $dataProvider = new ActiveDataProvider([
            'query'      => $query,
            'pagination' => ['pageSize' => 10],
            'sort'       => [
                // Default ordering if no sort column is clicked
                'defaultOrder' => ['priority' => SORT_DESC, 'due_date' => SORT_ASC],
                // Explicitly list which columns can be sorted by the user
                'attributes'   => ['title', 'status', 'priority', 'due_date', 'created_at'],
            ],
        ]);

        // Load all categories so the filter dropdown can list them
        $categories = Category::find()->orderBy(['name' => SORT_ASC])->all();

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'categories'   => $categories,
        ]);
    }

    /**
     * Displays full details of a single task.
     *
     * @param int $id Task primary key
     * @return string Rendered detail view
     * @throws NotFoundHttpException If the task does not exist or is soft-deleted
     */
    public function actionView(int $id): string
    {
        return $this->render('view', ['model' => $this->findModel($id)]);
    }

    /**
     * Displays the task creation form (GET) and processes the submitted form (POST).
     *
     * On a successful POST the task and its selected categories are saved inside a
     * database transaction so that either both writes succeed or neither does.
     *
     * @return string|Response Rendered form on GET/validation failure; redirect on success
     */
    public function actionCreate()
    {
        $model = new Task(); // New empty task with default status/priority set by init()

        if ($model->load(Yii::$app->request->post())) {
            // Queue the submitted category IDs; they are written in afterSave()
            $model->setCategoryIds(Yii::$app->request->post('category_ids', []));

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($model->save()) {
                    $transaction->commit();
                    Yii::$app->session->setFlash('success', 'Task created successfully.');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Could not save the task. Please check the form.');
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::error('Task creation failed: ' . $e->getMessage());
                Yii::$app->session->setFlash('error', 'An unexpected error occurred. Please try again.');
            }
        }

        $categories = Category::find()->orderBy(['name' => SORT_ASC])->all();
        return $this->render('create', ['model' => $model, 'categories' => $categories]);
    }

    /**
     * Displays the task edit form (GET) and processes the submitted form (POST).
     *
     * @param int $id Task primary key
     * @return string|Response Rendered form on GET/validation failure; redirect on success
     * @throws NotFoundHttpException
     */
    public function actionUpdate(int $id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            $model->setCategoryIds(Yii::$app->request->post('category_ids', []));

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if ($model->save()) {
                    $transaction->commit();
                    Yii::$app->session->setFlash('success', 'Task updated successfully.');
                    return $this->redirect(['view', 'id' => $model->id]);
                }
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Could not update the task. Please check the form.');
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::error('Task update failed: ' . $e->getMessage());
                Yii::$app->session->setFlash('error', 'An unexpected error occurred. Please try again.');
            }
        }

        $categories = Category::find()->orderBy(['name' => SORT_ASC])->all();
        // Pass the IDs of currently assigned categories so the form pre-checks them
        $selected   = $model->getCategoryIds();

        return $this->render('update', [
            'model'      => $model,
            'categories' => $categories,
            'selected'   => $selected,
        ]);
    }

    /**
     * Soft-deletes a task (moves it to the Trash).
     *
     * The task row is NOT removed from the database; instead, `deleted_at` is set
     * to the current timestamp.  The task can be restored from the Trash view.
     *
     * Requires POST (enforced by VerbFilter) to prevent accidental deletion via link.
     *
     * @param int $id Task primary key
     * @return Response Redirect to task list
     * @throws NotFoundHttpException
     */
    public function actionDelete(int $id): Response
    {
        $this->findModel($id)->softDelete();
        Yii::$app->session->setFlash('success', 'Task moved to Trash. You can restore it from the Trash view.');
        return $this->redirect(['index']);
    }

    // -------------------------------------------------------------------------
    // Status management
    // -------------------------------------------------------------------------

    /**
     * Cycles a task's status: pending → in_progress → completed → pending.
     *
     * Supports two response modes:
     *   • Normal (browser navigation): redirects back to the task list.
     *   • Ajax (JavaScript fetch): returns a JSON object so the page can update
     *     the status badge and button without a full page reload.
     *
     * @param int $id Task primary key
     * @return array|Response JSON array (Ajax) or redirect (normal request)
     * @throws NotFoundHttpException
     */
    public function actionChangeStatus(int $id)
    {
        $model = $this->findModel($id);

        // Advance the status to the next value in the cycle
        $cycle = [
            Task::STATUS_PENDING     => Task::STATUS_IN_PROGRESS,
            Task::STATUS_IN_PROGRESS => Task::STATUS_COMPLETED,
            Task::STATUS_COMPLETED   => Task::STATUS_PENDING,
        ];
        $model->status = $cycle[$model->status] ?? Task::STATUS_PENDING;
        $model->save(false); // skip full validation — only status changed

        // --- Ajax response ---
        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            // Badge info for the NEW status (what to display after the change)
            $badgeMap  = Task::statusBadgeMap();
            [$badgeClass, $badgeLabel] = $badgeMap[$model->status];

            // Button info for the NEXT status change (what icon/label to show on the button)
            $nextMap = [
                Task::STATUS_PENDING     => ['In Progress', 'bi-arrow-right-circle',  'btn-outline-primary'],
                Task::STATUS_IN_PROGRESS => ['Complete',    'bi-check-circle',         'btn-outline-success'],
                Task::STATUS_COMPLETED   => ['Reset',       'bi-arrow-counterclockwise','btn-outline-secondary'],
            ];
            [$nextTitle, $nextIcon, $nextBtnClass] = $nextMap[$model->status];

            return [
                'success'      => true,
                'status'       => $model->status,      // raw value (e.g. 'in_progress')
                'label'        => $badgeLabel,          // display text (e.g. 'In Progress')
                'badgeClass'   => $badgeClass,          // Bootstrap class (e.g. 'bg-primary')
                'nextTitle'    => $nextTitle,           // tooltip for the button
                'nextIcon'     => $nextIcon,            // Bootstrap Icon class
                'btnClass'     => $nextBtnClass,        // Bootstrap button class
            ];
        }

        // --- Normal (non-Ajax) response ---
        Yii::$app->session->setFlash('success', 'Task status updated.');
        return $this->redirect(['index']);
    }

    // -------------------------------------------------------------------------
    // Trash (soft-delete management)
    // -------------------------------------------------------------------------

    /**
     * Displays the Trash — all tasks that have been soft-deleted.
     * From here the user can restore a task or permanently delete it.
     *
     * @return string Rendered trash view
     */
    public function actionTrash(): string
    {
        // Use findWithTrashed() to bypass the soft-delete filter in Task::find(),
        // then narrow down to only the deleted rows (deleted_at IS NOT NULL).
        $query = Task::findWithTrashed()
                     ->andWhere(['IS NOT', '{{%tasks}}.deleted_at', null]);

        $dataProvider = new ActiveDataProvider([
            'query'      => $query,
            'pagination' => ['pageSize' => 10],
            'sort'       => ['defaultOrder' => ['deleted_at' => SORT_DESC]],
        ]);

        return $this->render('trash', ['dataProvider' => $dataProvider]);
    }

    /**
     * Restores a soft-deleted task by clearing its deleted_at timestamp.
     * The task will reappear in the normal task list after restoration.
     *
     * @param int $id Task primary key
     * @return Response Redirect to Trash view
     * @throws NotFoundHttpException
     */
    public function actionRestore(int $id): Response
    {
        $this->findTrashedModel($id)->restore();
        Yii::$app->session->setFlash('success', 'Task has been restored successfully.');
        return $this->redirect(['trash']);
    }

    /**
     * Permanently deletes a soft-deleted task from the database.
     * This action CANNOT be undone.
     *
     * @param int $id Task primary key
     * @return Response Redirect to Trash view
     * @throws NotFoundHttpException
     */
    public function actionForceDelete(int $id): Response
    {
        $this->findTrashedModel($id)->delete(); // calls Yii2's real DELETE query
        Yii::$app->session->setFlash('success', 'Task permanently deleted.');
        return $this->redirect(['trash']);
    }

    // -------------------------------------------------------------------------
    // CSV Export
    // -------------------------------------------------------------------------

    /**
     * Exports the current filtered task list as a downloadable CSV file.
     *
     * The export respects the same search/filter parameters as actionIndex(),
     * so the user gets exactly what they see on screen — just as a spreadsheet.
     *
     * @return Response File-download response containing the CSV
     */
    public function actionExport(): Response
    {
        // Re-build the same query as actionIndex() so filters are honoured
        $query = Task::find()->with('categories');

        $search = Yii::$app->request->get('search');
        if (!empty($search)) {
            $query->andWhere(['or',
                ['like', '{{%tasks}}.title', $search],
                ['like', '{{%tasks}}.description', $search],
            ]);
        }
        if ($status = Yii::$app->request->get('status')) {
            $query->andWhere(['{{%tasks}}.status' => $status]);
        }
        if ($priority = Yii::$app->request->get('priority')) {
            $query->andWhere(['{{%tasks}}.priority' => $priority]);
        }
        if ($categoryId = Yii::$app->request->get('category_id')) {
            $query->joinWith('categories')->andWhere(['{{%categories}}.id' => $categoryId]);
        }
        if ($dueDate = Yii::$app->request->get('due_date')) {
            $query->andWhere(['{{%tasks}}.due_date' => $dueDate]);
        }

        $tasks = $query->all();

        // Build the CSV content as a plain string
        // Each field is wrapped in double quotes; any embedded quote is escaped as ""
        $wrap = fn($v) => '"' . str_replace('"', '""', (string) ($v ?? '')) . '"';

        $lines   = [];
        // Header row
        $lines[] = implode(',', array_map($wrap, ['ID', 'Title', 'Description', 'Status', 'Priority', 'Due Date', 'Categories', 'Created At']));

        // Data rows
        foreach ($tasks as $task) {
            /** @var Task $task */
            $categoryNames = implode('; ', array_map(fn($c) => $c->name, $task->categories));
            $lines[] = implode(',', array_map($wrap, [
                $task->id,
                $task->title,
                $task->description,
                $task->status,
                $task->priority,
                $task->due_date,
                $categoryNames,
                $task->created_at,
            ]));
        }

        $csvContent  = implode("\r\n", $lines);
        $filename    = 'tasks-' . date('Y-m-d') . '.csv';

        // sendContentAsFile() sets the correct headers (Content-Disposition, mime type)
        // and streams the string as a file download — no need to write to disk.
        return Yii::$app->response->sendContentAsFile(
            $csvContent,
            $filename,
            ['mimeType' => 'text/csv', 'inline' => false]
        );
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Loads an active (non-deleted) task by primary key.
     * Throws a 404 exception if the task does not exist or has been soft-deleted.
     *
     * @param int $id Task primary key
     * @return Task
     * @throws NotFoundHttpException
     */
    protected function findModel(int $id): Task
    {
        // Task::find() already includes the deleted_at IS NULL filter
        if (($model = Task::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested task does not exist.');
    }

    /**
     * Loads a SOFT-DELETED task by primary key.
     * Used exclusively by actionRestore() and actionForceDelete().
     * Throws a 404 exception if the task does not exist or has NOT been soft-deleted.
     *
     * @param int $id Task primary key
     * @return Task
     * @throws NotFoundHttpException
     */
    protected function findTrashedModel(int $id): Task
    {
        // findWithTrashed() bypasses the soft-delete filter; we then require deleted_at IS NOT NULL
        $model = Task::findWithTrashed()
                     ->where(['{{%tasks}}.id' => $id])
                     ->andWhere(['IS NOT', '{{%tasks}}.deleted_at', null])
                     ->one();

        if ($model !== null) {
            return $model;
        }
        throw new NotFoundHttpException('The requested deleted task does not exist.');
    }
}
