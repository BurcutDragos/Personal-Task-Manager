# Personal Task Manager — PHP Yii2 Application

A fully-featured **Task Management System** built with [Yii 2 Framework](https://www.yiiframework.com/), MySQL, Bootstrap 5, and PHP 7.4+.

---

## 📱 Features

| Feature | Description |
|---------|-------------|
| **Task CRUD** | Create, view, update and soft-delete tasks |
| **Soft Delete / Trash** | Deleted tasks go to a Trash view and can be restored or permanently removed |
| **Status workflow** | pending → in_progress → completed, cycled via an **Ajax** button (no page reload) |
| **Priority levels** | low / medium / high, colour-coded badges |
| **Due-date tracking** | Overdue dates highlighted in red |
| **Category management** | Create named + colour-coded categories, assign multiple per task |
| **Search & Filter** | Filter by keyword, status, priority, category, due date |
| **Sorting & Pagination** | All columns sortable; 10 tasks per page |
| **CSV Export** | Download the current filtered view as a CSV spreadsheet |
| **Flash messages** | Success/error feedback after every action |
| **Database migrations** | Reproducible schema with seed data included |
| **Comprehensive comments** | Every file documented so any developer can follow the code |

---

## ✅ Requirements

| Tool | Version |
|------|---------|
| PHP  | 7.4 or 8.x |
| Composer | latest |
| MySQL / MariaDB | 5.7+ / 10.3+ |
| Web server | Apache (mod_rewrite) or PHP built-in server |

---

## 🚀 Setup

### 1. Install PHP dependencies

```bash
cd rockna-task-manager
composer install
```

> `vendor/` is git-ignored. This step is required before running the app.

### 2. Create the database

```sql
CREATE DATABASE rockna_tasks CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 3. Configure the database connection

Edit `config/db.php` and set your MySQL credentials:

```php
return [
    'class'    => 'yii\db\Connection',
    'dsn'      => 'mysql:host=127.0.0.1;dbname=rockna_tasks',
    'username' => 'root',
    'password' => '',          // set your password here
    'charset'  => 'utf8mb4',
];
```

### 4. Run migrations

```bash
php yii migrate --interactive=0
```

> Use the **project-level** `yii` script (not `vendor/bin/yii`).
> The project-level script loads `config/console.php` which includes the `db` component.
> `vendor/bin/yii` starts a bare Yii2 app with no database configured and will fail.

This creates the `categories`, `tasks`, `task_categories` tables and the `deleted_at` soft-delete column, and inserts seed data (3 example categories + 1 example task).

### 5. Start the development server

```bash
php -S localhost:8080 -t web
```

Open [http://localhost:8080](http://localhost:8080).

For Apache: point the DocumentRoot to the `web/` directory. The included `web/.htaccess` handles URL rewriting.

---

## ✅ Project Structure

```
rockna-task-manager/
├── config/
│   ├── db.php              # Database connection settings
│   ├── web.php             # Web application configuration
│   └── console.php         # Console application configuration (migrations)
├── controllers/
│   ├── TaskController.php      # CRUD, soft-delete, Ajax status, CSV export
│   └── CategoryController.php  # Category CRUD
├── migrations/
│   ├── m251101_000001_create_categories_table.php
│   ├── m251101_000002_create_tasks_table.php
│   ├── m251101_000003_create_task_categories_table.php
│   └── m260226_120000_add_soft_delete_to_tasks.php
├── models/
│   ├── Task.php            # ActiveRecord with soft delete, category sync, badge maps
│   ├── Category.php        # ActiveRecord for category labels
│   └── TaskCategory.php    # Pivot table ActiveRecord
├── views/
│   ├── layouts/main.php    # Bootstrap 5 layout + jQuery + flash messages
│   ├── task/
│   │   ├── index.php       # Task list with filters, Ajax status, export button
│   │   ├── create.php      # Create form wrapper
│   │   ├── update.php      # Edit form wrapper
│   │   ├── view.php        # Task detail (DetailView)
│   │   ├── _form.php       # Shared form partial (create + update)
│   │   └── trash.php       # Soft-deleted tasks: restore or delete forever
│   └── category/
│       ├── index.php       # Category list with colour swatches
│       ├── create.php
│       ├── update.php
│       └── _form.php
├── web/
│   ├── index.php           # Web entry point
│   └── .htaccess           # Apache URL rewriting
└── yii                     # Console entry point (use: php yii <command>)
```

---

## 🧠 Key Design Decisions

### 1. Soft Deletes — `deleted_at` column

Deleting a task sets `deleted_at` to the current timestamp instead of removing the row.
`Task::find()` is overridden to automatically append `WHERE deleted_at IS NULL` to every query, so soft-deleted tasks are invisible to normal listing/filtering code without any changes needed in controllers or views.
`Task::findWithTrashed()` bypasses the filter for the Trash view, restore, and force-delete actions.

### 2. Ajax Status Updates

`TaskController::actionChangeStatus()` detects `Yii::$app->request->isAjax` and returns a JSON response when called from JavaScript. The task list view (`views/task/index.php`) registers a jQuery click handler that intercepts the status button, posts via `$.post()`, and updates the badge text/colour and button icon in the same table row — no page reload required.

### 3. CSV Export

`TaskController::actionExport()` re-applies the same filter parameters as `actionIndex()` so the exported file matches exactly what the user sees on screen. The CSV is built as an in-memory string and sent via `Response::sendContentAsFile()` — no temp files on disk.

### 4. Category Saving — `afterSave()` Hook

Many-to-many category assignment is handled in `Task::afterSave()`. The controller calls `$model->setCategoryIds([...])` before `save()`, which caches the IDs. After the task row is written, `afterSave()` deletes all existing pivot rows for the task and inserts fresh ones. This runs inside the same transaction as the task `save()`.

### 5. Bootstrap 5 via CDN

Avoids bower-asset and npm-asset complexity entirely. jQuery, Bootstrap CSS, Bootstrap JS, and Bootstrap Icons all load from CDN. Yii2's `JqueryAsset` bundle is overridden in `config/web.php` to output nothing (empty `js:[]`), so no duplicate jQuery is loaded.

### 6. Transaction-based Writes

Create and Update actions wrap the `save()` call and category pivot sync in a `beginTransaction()` / `commit()` / `rollBack()` block so a partial failure leaves no inconsistent data.

---

## 📄 Security Measures

- `Html::encode()` on all user-supplied output (XSS prevention)
- ActiveRecord parameterised queries via PDO (SQL injection prevention)
- CSRF validation on all POST forms (Yii2 default; token embedded by `ActiveForm`)
- CSRF token included in Ajax POST requests (read from `meta[name="csrf-token"]`)
- `VerbFilter` restricts delete/restore/force-delete/change-status to POST only

---

## 💡 Evaluation Q&A

**Query optimisation for large datasets**
Add composite indexes on `(status, priority)` and `(due_date)`. Already using `with('categories')` to avoid N+1 queries. For full-text search at scale, consider MySQL FULLTEXT indexes or a dedicated search engine.

**Concurrent task updates**
Use optimistic locking: add a `version INT` column, attach `OptimisticLockBehavior`, and handle `StaleObjectException` in the controller to tell the user that another session modified the record.

**What I would add with more time**
- Unit tests (PHPUnit) for models and functional tests for controllers
- User authentication + per-user task ownership (`AccessControl` filter)
- Due-date email reminders (console cron command)
- Drag-and-drop Kanban board view

## 🤝 Contributing:
1. Fork the repository.
2. Create a new branch: `git checkout -b my-feature-branch`
3. Make your changes and commit them: `git commit -m 'Add some feature'`
4. Push to the branch: `git push origin my-feature-branch`
5. Submit a pull request.

## 📄 License:
This project is licensed under the MIT License - see the LICENSE file for details.

## 🧑‍💻 Author(s):
Burcut Ioan Dragos.

## 💡 Acknowledgments:
Thanks to Anthropic (Claude AI) for providing assistance in the development of this project.
