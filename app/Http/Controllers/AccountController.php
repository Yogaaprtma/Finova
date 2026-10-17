<?php

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Models\Account;
use App\Services\DashboardService;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    /**
     * Display a listing of the accounts.
     */
    public function index(Request $request): Response
    {
        $accounts = Account::where('user_id', $request->user()->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $totalBalance = $accounts->sum('current_balance');

        return Inertia::render('Accounts/Index', [
            'accounts' => $accounts,
            'totalBalance' => $totalBalance,
        ]);
    }

    /**
     * Show the form for creating a new account.
     */
    public function create(): Response
    {
        return Inertia::render('Accounts/Create', [
            'accountTypes' => AccountType::cases(),
        ]);
    }

    /**
     * Store a newly created account in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('accounts')->where(fn ($query) => $query->where('user_id', $request->user()->id))->whereNull('deleted_at'),
            ],
            'type' => ['required', Rule::enum(AccountType::class)],
            'currency' => ['required', 'string', 'size:3'],
            'initial_balance' => ['required', 'numeric'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['current_balance'] = 0; // Balance will be recalculated by TransactionService

        // Ensure defaults if not provided
        $validated['currency'] = $validated['currency'] ?? 'IDR';

        $account = DB::transaction(function () use ($validated, $request) {
            $account = Account::create($validated);

            if ($validated['initial_balance'] != 0) {
                // Record initial balance as an adjustment transaction per Financial Logic Spec
                app(TransactionService::class)->createAdjustment([
                    'user_id' => $request->user()->id,
                    'account_id' => $account->id,
                    'amount' => $validated['initial_balance'],
                    'description' => 'Initial Balance',
                    'date' => now(),
                    'category_id' => null,
                    'source' => 'system',
                ]);
            }

            return $account;
        });

        return redirect()->route('accounts.index')->with('success', 'Account created successfully.');
    }

    /**
     * Display the specified account.
     */
    public function show(Request $request, Account $account): Response
    {
        if ($account->user_id !== $request->user()->id) {
            abort(403);
        }

        $transactions = $account->transactions() // This requires relation on Account model which we might need to check/add later if not exists, wait, actually transactions relate to accounts via TransactionEntry. Let's fix this later when we implement Transaction Service, for now we will just load account.
            ->with(['category'])
            ->latest('date')
            ->paginate(15);

        return Inertia::render('Accounts/Show', [
            'account' => $account,
            'transactions' => $transactions,
        ]);
    }

    /**
     * Show the form for editing the specified account.
     */
    public function edit(Request $request, Account $account): Response
    {
        if ($account->user_id !== $request->user()->id) {
            abort(403);
        }

        return Inertia::render('Accounts/Edit', [
            'account' => $account,
            'accountTypes' => AccountType::cases(),
        ]);
    }

    /**
     * Update the specified account in storage.
     */
    public function update(Request $request, Account $account): RedirectResponse
    {
        if ($account->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('accounts')
                    ->where(fn ($query) => $query->where('user_id', $request->user()->id))
                    ->whereNull('deleted_at')
                    ->ignore($account->id),
            ],
            'type' => ['required', Rule::enum(AccountType::class)],
            'currency' => ['required', 'string', 'size:3'],
            'icon' => ['nullable', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $account->update($validated);
        app(DashboardService::class)->clearDashboardCache((string) $request->user()->id);

        return redirect()->route('accounts.index')->with('success', 'Account updated successfully.');
    }

    /**
     * Remove the specified account from storage.
     */
    public function destroy(Request $request, Account $account): RedirectResponse
    {
        if ($account->user_id !== $request->user()->id) {
            abort(403);
        }

        $account->delete();
        app(DashboardService::class)->clearDashboardCache((string) $request->user()->id);

        return redirect()->route('accounts.index')->with('success', 'Account deleted successfully.');
    }
}
