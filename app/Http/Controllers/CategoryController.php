<?php

namespace App\Http\Controllers;

use App\Enums\CategoryType;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $categories = Category::where('user_id', $request->user()->id)
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->orderBy('sort_order')->orderBy('name')])
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Reorder categories by updating sort_order.
     */
    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'categories' => ['required', 'array'],
            'categories.*.id' => ['required', 'exists:categories,id'],
            'categories.*.sort_order' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated['categories'] as $item) {
            Category::where('id', $item['id'])
                ->where('user_id', $request->user()->id)
                ->update(['sort_order' => $item['sort_order']]);
        }

        return redirect()->route('categories.index')->with('success', 'Categories reordered successfully.');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $parentCategories = Category::where('user_id', $request->user()->id)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get();

        return Inertia::render('Categories/Create', [
            'parentCategories' => $parentCategories,
            'categoryTypes' => CategoryType::cases(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(CategoryType::class)],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $request->user()->categories()->create($validated);

        return redirect()->route('categories.index')->with('success', 'Category created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Category $category): Response
    {
        if ($category->user_id !== $request->user()->id) {
            abort(403);
        }

        $parentCategories = Category::where('user_id', $request->user()->id)
            ->whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->orderBy('name')
            ->get();

        return Inertia::render('Categories/Edit', [
            'category' => $category,
            'parentCategories' => $parentCategories,
            'categoryTypes' => CategoryType::cases(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        if ($category->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(CategoryType::class)],
            'parent_id' => ['nullable', 'exists:categories,id', 'different:id'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $category->update($validated);

        return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Category $category): RedirectResponse
    {
        if ($category->user_id !== $request->user()->id) {
            abort(403);
        }

        if ($category->is_system) {
            return redirect()->route('categories.index')->with('error', 'Cannot delete system categories.');
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'Category deleted successfully.');
    }
}
