# Task Manager

A personal task manager built with **Laravel**, **Inertia.js** and **Vue 3**. It started as an internship project and as a way to learn Inertia, and it grew into a small but complete app: tasks, categories, repeating tasks, a calendar, a dashboard with streaks, and in-app reminders.

<!-- Add screenshots here, for example: ![Dashboard](docs/screenshots/dashboard.png) -->

## Features

### Tasks

- Create, edit and delete tasks with a title, description, due date, priority (Low, Medium, High, Urgent) and a required category
- Complete and uncomplete tasks with a checkbox
- Due-date labels: Overdue, Due today, Due tomorrow
- **Undo after delete:** a deleted task can be restored from the toast, and is removed for good after 7 days
- Filter by category, priority, due date range and completed; search by title; pagination
- Keyboard shortcuts: **N** for a new task, **/** to focus the search box, **Esc** to clear it

### Repeating tasks

- Daily, weekly and monthly presets
- **Custom repeat:** every N days, weeks or months, with optional weekdays (for example "every Monday and Thursday")
- Completing a repeating task creates the next one on the next date that fits the rule, even if you finish it late or early

### Categories

- Create, rename and delete your own categories
- Names are unique per user, and a category cannot be deleted while it still has tasks
- Every new user starts with five default categories

### Dashboard

- Personal greeting with a one-line summary of your day
- Stat cards for tasks due today, overdue tasks and open tasks
- **Progress card** for tasks due today, this week and this month, with a completed count and percentage
- **Streaks:** current and best streak of days with at least one completed task
- **Weekly chart** of completed tasks per day, compared with the same days of last week
- A "Focus now" list where you can complete tasks directly, and open tasks broken down by priority and category

### Calendar

- Month grid with task chips on each day and a "+N more" indicator
- Select a day to see its tasks in a side panel, where you can add, edit, delete and complete them
- Previous, next and Today navigation, with the month kept in the URL (`/calendar?month=2026-11`)

### Notifications

- A reminder every day for tasks due today or overdue, stored in the database
- A bell with an unread badge, a dropdown list, mark as read and mark all as read

### General

- Success and error toasts for every action
- Light and dark theme
- Responsive layout and a landing page

## Tech stack

| Area     | Technology                                              |
| -------- | ------------------------------------------------------- |
| Backend  | Laravel 12, PHP 8.2+                                    |
| Frontend | Vue 3 (Composition API), TypeScript, Inertia.js 2       |
| UI       | Tailwind CSS 3, shadcn-vue, Lucide icons, Sonner toasts |
| Database | MySQL                                                   |
| Tooling  | Vite, pnpm, PHPUnit                                     |

## Requirements

- PHP 8.2 or newer and Composer 2
- Node.js 20 or newer and pnpm
- MySQL 8

## Getting started

```bash
git clone https://github.com/Mustafa21102005/Task-Manager-VioletPro.git
cd task-manager

composer install
pnpm install

cp .env.example .env
php artisan key:generate
```

Open `.env` and set your database and timezone:

```env
APP_NAME="Task Manager"
APP_TIMEZONE=Asia/Riyadh   # use your own; "due today" depends on it

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_manager
DB_USERNAME=root
DB_PASSWORD=
```

Create the `task_manager` database, then:

```bash
php artisan migrate --seed
```

Start the app in two terminals:

```bash
pnpm dev            # Vite
php artisan serve   # the app, at http://localhost:8000
```

### Demo account

Seeding creates 5 users, each with categories and tasks. For a quick look around:

| Email            | Password   |
| ---------------- | ---------- |
| `user@gmail.com` | `password` |

The demo user has a six-day streak, so the dashboard looks lively. The streak is relative to the day you seed, so run `php artisan migrate:fresh --seed` shortly before a demo.

> These accounts are for local development only. Do not seed them on a public server.

## Scheduled jobs

| Command          | When  | What it does                                                     |
| ---------------- | ----- | ---------------------------------------------------------------- |
| `reminders:send` | Daily | Creates one reminder per user who has tasks due today or overdue |
| `model:prune`    | Daily | Permanently removes tasks that were deleted more than 7 days ago |

Locally, keep the scheduler running in a third terminal:

```bash
php artisan schedule:work
```

## Running the tests

The app uses MySQL-specific SQL, so the tests run against a **separate MySQL database**, never your development one. `tests/TestCase.php` refuses to run on any database whose name does not contain `testing`.

```bash
php artisan test
```

The suite has over 100 tests: unit tests for the repeat-date logic, and feature tests for tasks, categories, the dashboard, the calendar, notifications and cleanup, plus the authentication tests that ship with the starter kit.

Type-check and build the frontend with:

```bash
pnpm exec vue-tsc --noEmit
pnpm build
```

## Design notes

- **Props are the state.** There is no client-side store. Every page receives its data from Laravel as Inertia props, and every action ends with a redirect that refreshes them.
- **Ownership everywhere.** Queries are scoped to the logged-in user, and policies guard update, delete and restore.
- **Flash messages become toasts.** Controllers flash a message into the session, Laravel shares it as a prop, and one component turns it into a toast. The Undo link travels the same way.
- **Repeating tasks are created on completion,** not by a schedule. A forgotten weekly task does not pile up as ten overdue copies. `RecurrenceRule` finds the next date that fits.
- **Partial reloads** keep navigation light: month changes on the calendar, search on the tasks page and the notification bell refresh only the props they need.
- **Soft deletes** make Undo possible, and a scheduled prune keeps the table clean.

## License

This project is open source under the [MIT License](LICENSE).

## Author

**Mustafa Azmi Khalil** — © 2026
