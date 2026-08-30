<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\BudgetService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    public function __construct(
        private BudgetService $budgetService
    ) {}

    /**
     * Display a listing of budgets.
     */
    public function index(Request $request): Response
    {
        $budgets = $this->budgetService->getBudgetsForUser($request->user());

        $formatted = $budgets->map(function (Budget $budget) {
            return $this->budgetService->getBudgetWithProgress($budget);
        });

        return Inertia::render('Budgets/Index', [
            'budgets' => $formatted,
        ]);
    }

    /**
     * Show the form for creating a new budget.
     */
    public function create(Request $request): Response
    {
        $userId = $request->user()->id;

        $categories = Category::where('user_id', $userId)
            ->where('type', 'expense')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'icon', 'color']);

        return Inertia::render('Budgets/Create', [
            'categories' => $categories,
            'defaultPeriod' => [
                'start' => Carbon::today()->startOfMonth()->format('Y-m-d'),
                'end' => Carbon::today()->endOfMonth()->format('Y-m-d'),
                'name' => Carbon::today()->translatedFormat('F Y'),
            ],
        ]);
    }

    /**
     * Store a newly created budget.
     */
    public function store(Request $request): RedirectResponse
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'period_start' => [
                'required',
                'date',
                function ($attribute, $value, $fail) use ($userId, $request) {
                    $exists = Budget::where('user_id', $userId)
                        ->whereDate('period_start', $value)
                        ->whereDate('period_end', $request->input('period_end'))
                        ->exists();

                    if ($exists) {
                        $fail('Anggaran untuk periode ini sudah pernah dibuat.');
                    }
                },
            ],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'total_budget' => ['nullable', 'integer', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.category_id' => [
                'required',
                Rule::exists('categories', 'id')->where('user_id', $userId),
            ],
            'items.*.amount' => ['required', 'integer', 'min:1'],
        ], [
            'period_start.unique' => 'Anggaran untuk periode ini sudah pernah dibuat.',
        ]);

        $budget = $this->budgetService->createBudget(
            $request->user(),
            [
                'name' => $validated['name'],
                'period_start' => $validated['period_start'],
                'period_end' => $validated['period_end'],
                'total_budget' => $validated['total_budget'] ?? null,
            ],
            $validated['items']
        );

        return redirect()->route('budgets.show', $budget->id)
            ->with('success', 'Anggaran berhasil dibuat.');
    }

    /**
     * Display the specified budget progress and activity.
     */
    public function show(Request $request, Budget $budget): Response
    {
        if ($budget->user_id !== $request->user()->id) {
            abort(403);
        }

        $budgetProgress = $this->budgetService->getBudgetWithProgress($budget);

        $categoryIds = collect($budgetProgress['items'])->pluck('category_id')->filter()->all();

        $transactions = Transaction::with(['category', 'entries.account'])
            ->where('user_id', $request->user()->id)
            ->where('type', 'expense')
            ->whereIn('category_id', $categoryIds)
            ->whereBetween('date', [$budgetProgress['period_start'], $budgetProgress['period_end']])
            ->orderBy('date', 'desc')
            ->limit(20)
            ->get();

        return Inertia::render('Budgets/Show', [
            'budget' => $budgetProgress,
            'recentTransactions' => $transactions,
        ]);
    }

    /**
     * Show the form for editing the specified budget.
     */
    public function edit(Request $request, Budget $budget): Response
    {
        if ($budget->user_id !== $request->user()->id) {
            abort(403);
        }

        $userId = $request->user()->id;

        $categories = Category::where('user_id', $userId)
            ->where('type', 'expense')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'icon', 'color']);

        $budgetProgress = $this->budgetService->getBudgetWithProgress($budget);

        return Inertia::render('Budgets/Edit', [
            'budget' => $budgetProgress,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified budget.
     */
    public function update(Request $request, Budget $budget): RedirectResponse
    {
        if ($budget->user_id !== $request->user()->id) {
            abort(403);
        }

        $userId = $request->user()->id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'period_start' => [
                'required',
                'date',
                function ($attribute, $value, $fail) use ($userId, $request, $budget) {
                    $exists = Budget::where('user_id', $userId)
                        ->where('id', '!=', $budget->id)
                        ->whereDate('period_start', $value)
                        ->whereDate('period_end', $request->input('period_end'))
                        ->exists();

                    if ($exists) {
                        $fail('Anggaran untuk periode ini sudah pernah dibuat.');
                    }
                },
            ],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'total_budget' => ['nullable', 'integer', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.category_id' => [
                'required',
                Rule::exists('categories', 'id')->where('user_id', $userId),
            ],
            'items.*.amount' => ['required', 'integer', 'min:1'],
        ]);

        $this->budgetService->updateBudget(
            $budget,
            [
                'name' => $validated['name'],
                'period_start' => $validated['period_start'],
                'period_end' => $validated['period_end'],
                'total_budget' => $validated['total_budget'] ?? null,
            ],
            $validated['items']
        );

        return redirect()->route('budgets.show', $budget->id)
            ->with('success', 'Anggaran berhasil diperbarui.');
    }

    /**
     * Remove the specified budget.
     */
    public function destroy(Request $request, Budget $budget): RedirectResponse
    {
        if ($budget->user_id !== $request->user()->id) {
            abort(403);
        }

        $this->budgetService->deleteBudget($budget);

        return redirect()->route('budgets.index')
            ->with('success', 'Anggaran berhasil dihapus.');
    }
}
