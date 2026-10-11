# CSN Platform — API

Laravel API backend for the CSN Platform — a project/task management system where clients create projects and leave feedback, team members handle tasks and files, and admins oversee everything. Client feedback can be AI-simplified into a sentiment, priority, and suggested action.

This is the backend only. The frontend (React + Vite) lives in a separate repo and talks to this API over HTTP with a Sanctum bearer token.

## Roles

| Role    | Can do |
|---------|--------|
| `client` | Create projects, upload feedback on files, view their own projects/files |
| `team`   | Upload/manage files, comment, work tasks assigned to them |
| `admin`  | Everything — assign members/tasks, update roles, complete/hold projects, delete, view admin stats and the activity log |

## Tech stack

- Laravel
- Laravel Sanctum — token-based API auth
- OpenAPI attributes (`OpenApi\Attributes`) on controllers for API docs generation
- Local `public` disk for file storage

## Project structure

```
app/
  Http/Controllers/
    AuthController.php           register / login / logout
    DashboardController.php      role-specific dashboard summary
    ProjectController.php        projects: create, list, assign member, complete, hold, delete
    TaskController.php           tasks: create, list, assign, update status, delete
    TaskCommentController.php    task comments (not yet used by any frontend)
    FileController.php           file upload/download/delete
    FeedbackController.php       client feedback + AI simplification
    UserController.php           current user, user list
    adminController.php          admin statistics, role updates
    ActivityLogController.php    filterable/paginated activity log
    NotificationController.php   stub — no notification feature implemented yet
  Models/                        Eloquent models
routes/api.php                   all API routes
```

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan storage:link   # required — files are served from the public disk
php artisan serve
```

Configure `.env` for your DB, and set `SANCTUM_STATEFUL_DOMAINS` / CORS to allow the frontend's origin (e.g. `http://localhost:5173`) if you run into auth/cookie issues. If the frontend and backend are on different ports/hosts, make sure the frontend's axios base URL points at this API's `/api` prefix.

## Known quirks / things to be aware of

- **The `Project` status column is genuinely named `statue`** (not `status`) — it's a typo baked into the schema across `ProjectController`, `DashboardController`, and `adminController`, not a bug. Don't rename it without also updating every place that reads it. `Task` status is spelled correctly (`status`).
- Several permission checks (`FileController`, `ProjectController`, `TaskController`, `TaskCommentController`) return `200 OK` with `{"message": "no"}` for forbidden actions instead of a proper `403`. Any client relying on HTTP status to detect failure will treat these as successes. Worth tightening to return `response()->json([...], 403)`.
- `TaskCommentController::destroy` has an inverted condition — as written, `if ($id !== $owner_id || $role !== 'admin')` denies the delete unless the user is *both* the comment's owner *and* an admin. That means a comment's actual author can never delete their own comment. This should almost certainly be `&&`.
- `TaskCommentController` and `NotificationController` exist but have no frontend consumer yet.
- Feedback `type` accepts four values: `comment`, `approval`, `change_request`, `blocked`.
- File uploads are capped at 100MB (`max:102400` in KB) on both project attachments and project files.

## API overview

| Resource | Routes |
|---|---|
| Auth | `POST /register`, `POST /login`, `POST /logout` |
| Dashboard | `GET /dashboard` |
| Projects | `GET/POST /project(s)`, `GET /project/{project}`, `POST /project/{project}/assign/{user}`, `PUT /project/{project}/complete`, `PUT /project/{project}/hold`, `DELETE /project/{project}` |
| Tasks | `GET/POST /tasks/{project}`, `POST /task/{task}/assign/{user}`, `PUT /task/{task}`, `DELETE /task/{task}`, `GET /tasks/mine` |
| Files | `GET/POST /projects/{project}/files`, `GET /files/{file}/download`, `DELETE /files/{file}` |
| Feedback | `GET/POST /file/{file}/feedback`, `GET /feedback/{feedback}/simplify` |
| Task Comments | `GET/POST /comment/{task}`, `DELETE /comment/{comment}` (unused by frontend) |
| Admin | `GET /admin/statistic`, `PUT /admin/{user}` |
| Activity Log | `GET /activity-logs` |