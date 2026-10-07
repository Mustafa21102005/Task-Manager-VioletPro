<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $categories = $request->user()
            ->categories()
            ->withCount('tasks')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn(Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'tasks_count' => $category->tasks_count,
            ]);

        if ($categories->isEmpty() && $categories->currentPage() > 1) {
            $request->session()->reflash();

            return redirect()->route('categories.index', ['page' => $categories->lastPage()]);
        }

        return Inertia::render('categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function store(StoreCategoryRequest $request)
    {
        $request->user()->categories()->create($request->validated());

        return back()->with('success', 'Category created successfully!');
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $this->authorize('update', $category);
        $category->update($request->validated());

        return back()->with('success', 'Category updated successfully!');
    }

    public function destroy(Category $category)
    {
        $this->authorize('delete', $category);

        if ($category->tasks()->exists()) {
            return back()->with('error', "You can't delete a category that still has tasks.");
        }

        $category->tasks()->onlyTrashed()->forceDelete();

        $category->delete();

        return back()->with('success', 'Category deleted successfully!');
    }
}
