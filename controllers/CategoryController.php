<?php

/**
 * CategoryController — handles all HTTP actions for the Category resource.
 *
 * Categories are colour-coded labels that can be attached to tasks.
 * This controller provides a standard CRUD interface (index, create, update,
 * delete) with flash messages for feedback after each mutation.
 *
 * Security note: the `delete` action is protected by VerbFilter so it can only
 * be reached via HTTP POST, preventing accidental deletion through GET requests
 * (e.g. from search-engine crawlers or prefetch).
 */

namespace app\controllers;

use Yii;
use app\models\Category;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;

/**
 * Class CategoryController
 *
 * Routes handled (all under /category/):
 *   GET  /category/index         → actionIndex()   — paginated list of all categories
 *   GET  /category/create        → actionCreate()  — blank create form
 *   POST /category/create        → actionCreate()  — save new category
 *   GET  /category/update?id=N   → actionUpdate()  — pre-filled edit form
 *   POST /category/update?id=N   → actionUpdate()  — save updated category
 *   POST /category/delete?id=N   → actionDelete()  — delete a category
 */
class CategoryController extends Controller
{
    // -------------------------------------------------------------------------
    // Behaviors (filters applied before every action)
    // -------------------------------------------------------------------------

    /**
     * Attaches Yii2 behavior filters to this controller.
     *
     * VerbFilter enforces that the `delete` action is only reachable via HTTP
     * POST.  Any GET request to /category/delete will receive a 405 response.
     *
     * @return array
     */
    public function behaviors(): array
    {
        return [
            'verbs' => [
                'class'   => VerbFilter::class,
                'actions' => [
                    // Prevent accidental GET-based deletion
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Actions
    // -------------------------------------------------------------------------

    /**
     * Displays a paginated list of all categories.
     *
     * The GridView in the view uses the ActiveDataProvider to automatically
     * handle pagination and sorting without any manual SQL.
     *
     * @return string Rendered HTML response
     */
    public function actionIndex(): string
    {
        // Build an ActiveDataProvider over the Category query.
        // The provider wraps ActiveRecord, so Yii2 handles COUNT and LIMIT/OFFSET.
        $dataProvider = new ActiveDataProvider([
            'query'      => Category::find(),
            'pagination' => ['pageSize' => 20],
            'sort'       => [
                // Default ordering: newest categories appear first
                'defaultOrder' => ['created_at' => SORT_DESC],
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays the "Create Category" form (GET) and saves a new category (POST).
     *
     * On GET:  renders the empty form via `create.php`.
     * On POST: loads submitted data into the model, validates, and saves.
     *          On success → redirect to index with a success flash.
     *          On failure → re-render the form with validation errors shown.
     *
     * @return string|\yii\web\Response
     */
    public function actionCreate()
    {
        $model = new Category();

        // load() fills the model from $_POST['Category'][...] and returns true
        // if the data was present.  save() validates and writes to the DB.
        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Category created successfully.');
            return $this->redirect(['index']);
        }

        return $this->render('create', ['model' => $model]);
    }

    /**
     * Displays the "Edit Category" form (GET) and saves changes (POST).
     *
     * The category is loaded by primary key; a 404 is thrown if it is not found.
     *
     * On GET:  renders the pre-filled form via `update.php`.
     * On POST: loads submitted data, validates, and saves.
     *          On success → redirect to index with a success flash.
     *          On failure → re-render the form with inline validation errors.
     *
     * @param int $id Primary key of the category to edit
     * @return string|\yii\web\Response
     * @throws NotFoundHttpException if no category with the given ID exists
     */
    public function actionUpdate(int $id)
    {
        // Loads the category or throws 404
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', 'Category updated successfully.');
            return $this->redirect(['index']);
        }

        return $this->render('update', ['model' => $model]);
    }

    /**
     * Permanently deletes a category (POST only).
     *
     * Note: Yii2's ActiveRecord `delete()` will also cascade-delete any related
     * rows if the database has ON DELETE CASCADE on the task_categories FK.
     * If not, pivot rows are left orphaned — acceptable for this scope.
     *
     * @param int $id Primary key of the category to delete
     * @return \yii\web\Response Redirect to the category index
     * @throws NotFoundHttpException if no category with the given ID exists
     */
    public function actionDelete(int $id): \yii\web\Response
    {
        $this->findModel($id)->delete();

        Yii::$app->session->setFlash('success', 'Category deleted successfully.');
        return $this->redirect(['index']);
    }

    // -------------------------------------------------------------------------
    // Protected helpers
    // -------------------------------------------------------------------------

    /**
     * Loads a Category model by primary key, throwing a 404 if it is missing.
     *
     * This helper is used by actionUpdate() and actionDelete() to avoid
     * repeating the same lookup-or-404 pattern.
     *
     * @param int $id Primary key to look up
     * @return Category The loaded model
     * @throws NotFoundHttpException
     */
    protected function findModel(int $id): Category
    {
        $model = Category::findOne($id);

        if ($model === null) {
            throw new NotFoundHttpException('The requested category does not exist.');
        }

        return $model;
    }
}
