<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\RecurringTransaction;
use App\Services\RecurringTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RecurringTransactionController extends Controller
{
    public function __construct(
        private RecurringTransactionService $recurringService
    ) {}

    /**
     * Display a listing of recurring transaction templates.
     */
    public function index(Request $request): Response
    {
        $recurring = $this->recurringService->getForUser($request->user());

        $formatted = $recurring->map(function (RecurringTransaction $r) {
            return [
                'id' => $r->id,
                'name' => $r->name,
                'type' => $r->type,
                'amount' => $r->amount,
                'currency' => $r->currency,
                'description' => $r->description,
                'frequency' => $r->frequency,
                'day_of_month' => $r->day_of_month,
                'day_of_week' => $r->day_of_week,
                'start_date' => $r->start_date?->format('Y-m-d'),
                'end_date' => $r->end_date?->format('Y-m-d'),
                'is_active' => $r->is_active,
                'auto_confirm' => $r->auto_confirm,
                'last_generated_date' => $r->last_generated_date?->format('Y-m-d'),
                'next_due_date' => $r->getNextDueDate()?->format('Y-m-d'),
                'category' => $r->category ? [
                    'id' => $r->category->id,
                    'name' => $r->category->name,
                    'icon' => $r->category->icon,
                    'color' => $r->category->color,
                ] : null,
                'account' => $r->account ? [
                    'id' => $r->account->id,
                    'name' => $r->account->name,
                    'type' => $r->account->type,
                ] : null,
            ];
        });

        return Inertia::render('RecurringTransactions/Index', [
            'recurringTransactions' => $formatted,
        ]);
    }

    /**
     * Show the form for creating a new recurring transaction template.
     */
    public function create(Request $request): Response
    {
        $userId = $request->user()->id;

        $accounts = Account::where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'current_balance']);

        $categories = Category::where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'icon', 'color']);

        return Inertia::render('RecurringTransactions/Create', [
            'accounts' => $accounts,
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created recurring transaction template.
     */
    public function store(Request $request): RedirectResponse
    {
        $userId = $request->user()->id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'amount' => ['required', 'integer', 'min:1'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'account_id' => [
                'required',
                Rule::exists('accounts', 'id')->where('user_id', $userId),
            ],
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where('user_id', $userId),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'frequency' => ['required', Rule::in(['daily', 'weekly', 'biweekly', 'monthly', 'yearly'])],
            'day_of_month' => ['nullable', 'integer', 'min:1', 'max:28'],
            'day_of_week' => ['nullable', 'integer', 'min:0', 'max:6'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['boolean'],
            'auto_confirm' => ['boolean'],
        ]);

        $this->recurringService->create($request->user(), $validated);

        return redirect()->route('recurring-transactions.index')
            ->with('success', 'Template transaksi berulang berhasil dibuat.');
    }

    /**
     * Show the form for editing the specified recurring transaction template.
     */
    public function edit(Request $request, RecurringTransaction $recurringTransaction): Response
    {
        if ($recurringTransaction->user_id !== $request->user()->id) {
            abort(403);
        }

        $userId = $request->user()->id;

        $accounts = Account::where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'current_balance']);

        $categories = Category::where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'icon', 'color']);

        return Inertia::render('RecurringTransactions/Edit', [
            'recurring' => [
                'id' => $recurringTransaction->id,
                'name' => $recurringTransaction->name,
                'type' => $recurringTransaction->type,
                'amount' => $recurringTransaction->amount,
                'currency' => $recurringTransaction->currency,
                'account_id' => $recurringTransaction->account_id,
                'category_id' => $recurringTransaction->category_id,
                'description' => $recurringTransaction->description,
                'frequency' => $recurringTransaction->frequency,
                'day_of_month' => $recurringTransaction->day_of_month,
                'day_of_week' => $recurringTransaction->day_of_week,
                'start_date' => $recurringTransaction->start_date?->format('Y-m-d'),
                'end_date' => $recurringTransaction->end_date?->format('Y-m-d'),
                'is_active' => $recurringTransaction->is_active,
                'auto_confirm' => $recurringTransaction->auto_confirm,
            ],
            'accounts' => $accounts,
            'categories' => $categories,
        ]);
    }

    /**
     * Update the specified recurring transaction template.
     */
    public function update(Request $request, RecurringTransaction $recurringTransaction): RedirectResponse
    {
        if ($recurringTransaction->user_id !== $request->user()->id) {
            abort(403);
        }

        $userId = $request->user()->id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'amount' => ['required', 'integer', 'min:1'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'account_id' => [
                'required',
                Rule::exists('accounts', 'id')->where('user_id', $userId),
            ],
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where('user_id', $userId),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'frequency' => ['required', Rule::in(['daily', 'weekly', 'biweekly', 'monthly', 'yearly'])],
            'day_of_month' => ['nullable', 'integer', 'min:1', 'max:28'],
            'day_of_week' => ['nullable', 'integer', 'min:0', 'max:6'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['boolean'],
            'auto_confirm' => ['boolean'],
        ]);

        $this->recurringService->update($recurringTransaction, $validated);

        return redirect()->route('recurring-transactions.index')
            ->with('success', 'Template transaksi berulang berhasil diperbarui.');
    }

    /**
     * Remove the specified recurring transaction template.
     */
    public function destroy(Request $request, RecurringTransaction $recurringTransaction): RedirectResponse
    {
        if ($recurringTransaction->user_id !== $request->user()->id) {
            abort(403);
        }

        $this->recurringService->delete($recurringTransaction);

        return redirect()->route('recurring-transactions.index')
            ->with('success', 'Template transaksi berulang berhasil dihapus.');
    }

    /**
     * Toggle active status.
     */
    public function toggle(Request $request, RecurringTransaction $recurringTransaction): RedirectResponse
    {
        if ($recurringTransaction->user_id !== $request->user()->id) {
            abort(403);
        }

        $this->recurringService->toggleActive($recurringTransaction);

        return back()->with('success', 'Status template berulang diperbarui.');
    }

    /**
     * Manually trigger immediate generation of transaction for testing.
     */
    public function generateNow(Request $request, RecurringTransaction $recurringTransaction): RedirectResponse
    {
        if ($recurringTransaction->user_id !== $request->user()->id) {
            abort(403);
        }

        $this->recurringService->generateForTemplate($recurringTransaction);

        return back()->with('success', 'Transaksi berulang berhasil digenerate sekarang.');
    }
}
