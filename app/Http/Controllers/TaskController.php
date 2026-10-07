<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Category;
use App\Models\Task;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): Response|RedirectResponse
    {
        $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $tasks = Task::query()
            ->where('user_id', $request->user()->id)
            ->with('category:id,name')
            ->when($request->string('category')->toString(), function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($request->string('priority')->toString(), function ($query, $priority) {
                $query->where('priority', $priority);
            })
            ->when($request->boolean('completed', null), function ($query) {
                $query->where('is_completed', true);
            })
            ->when($request->string('search')->trim()->toString(), function ($query, $search) {
                $query->where('title', 'like', '%' . addcslashes($search, '%_\\') . '%');
            })
            ->when($request->date('date_from'), function ($query, $dateFrom) {
                $query->whereDate('due_date', '>=', $dateFrom);
            })
            ->when($request->date('date_to'), function ($query, $dateTo) {
                $query->whereDate('due_date', '<=', $dateTo);
            })
            ->orderByRaw('is_completed ASC')
            ->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low')")
            ->orderBy('due_date', 'asc')
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString()
            ->through(fn(Task $task) => TaskResource::make($task)->resolve());

        if ($tasks->isEmpty() && $tasks->currentPage() > 1) {
            $request->session()->reflash();

            return redirect()->route('tasks.index', [
                ...$request->query(),
                'page' => $tasks->lastPage(),
            ]);
        }

        $categories = Category::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('tasks/Index', [
            'tasks' => $tasks,
            'categories' => $categories,
            'filters' => $request->only([
                'category',
                'priority',
                'completed',
                'search',
                'date_from',
                'date_to',
            ]),
        ]);
    }


    public function store(StoreTaskRequest $request)
    {
        $request->user()->tasks()->create($request->validated());

        return back()->with('success', 'Task created successfully!');
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $this->authorize('update', $task);

        $task->update($request->validated());

        return back()->with('success', 'Task updated successfully!');
    }

    public function destroy(Task $task)
    {
        $this->authorize('delete', $task);

        $task->delete();

        return back()
            ->with('success', 'Task deleted successfully!')
            ->with('undo', route('tasks.restore', $task, absolute: false));
    }

    public function restore(Task $task)
    {
        $this->authorize('restore', $task);

        $task->restore();

        return back()->with('success', 'Task restored successfully!');
    }

    public function toggleComplete(Request $request, Task $task)
    {
        $this->authorize('toggleComplete', $task);

        $completing = ! $task->is_completed;
        $next = null;

        DB::transaction(function () use ($request, $task, $completing, &$next) {
            $task->update([
                'is_completed' => $completing,
                'completed_at' => $completing ? now() : null,
            ]);

            // Repeating task being completed for the first time: create the next one
            if ($completing && $task->recurrence && $task->due_date && ! $task->spawned_next) {
                $next = $request->user()->tasks()->create([
                    'title' => $task->title,
                    'description' => $task->description,
                    'due_date' => $task->recurrenceRule()->nextDate($task->due_date, today()),
                    'priority' => $task->priority,
                    'category_id' => $task->category_id,
                    'recurrence' => $task->recurrence,
                    'recurrence_interval' => $task->recurrence_interval,
                    'recurrence_unit' => $task->recurrence_unit,
                    'recurrence_days' => $task->recurrence_days,
                ]);

                $task->update(['spawned_next' => true]);
            }
        });

        $message = match (true) {
            $next !== null => 'Done! The next one is due ' . $next->due_date->format('M j') . '.',
            $completing => 'Task completed!',
            default => 'Task marked as pending.',
        };

        return back()->with('success', $message);
    }
}
