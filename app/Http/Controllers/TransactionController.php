<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Account;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use App\Enums\TransactionType;
use App\Enums\EntryType;
use App\Services\TransactionService;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $transactions = Transaction::where('user_id', $request->user()->id)
            ->with(['category', 'entries.account'])
            ->latest('date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        $accounts = Account::where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $categories = Category::where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get();

        return Inertia::render('Transactions/Create', [
            'accounts' => $accounts,
            'categories' => $categories,
            'transactionTypes' => TransactionType::cases(),
            'defaultAccountId' => $request->query('account_id'),
            'type' => $request->query('type', 'expense'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::enum(TransactionType::class)],
            'amount' => ['required', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'notes' => ['nullable', 'string'],
            
            // For income/expense
            'account_id' => ['required_unless:type,transfer', 'exists:accounts,id'],
            
            // For transfer
            'source_account_id' => ['required_if:type,transfer', 'exists:accounts,id'],
            'destination_account_id' => ['required_if:type,transfer', 'exists:accounts,id', 'different:source_account_id'],
        ]);

        $user = $request->user();
        
        // Convert string enum to Enum instance if needed, wait, validation uses string
        $type = TransactionType::tryFrom($validated['type']);

        if ($type === TransactionType::INCOME) {
            $this->transactionService->createIncome([
                'user_id' => $user->id,
                'account_id' => $validated['account_id'],
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'date' => $validated['date'],
                'category_id' => $validated['category_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);
        } elseif ($type === TransactionType::EXPENSE) {
            $this->transactionService->createExpense([
                'user_id' => $user->id,
                'account_id' => $validated['account_id'],
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'date' => $validated['date'],
                'category_id' => $validated['category_id'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);
        } elseif ($type === TransactionType::TRANSFER) {
            $this->transactionService->createTransfer([
                'user_id' => $user->id,
                'from_account_id' => $validated['source_account_id'],
                'to_account_id' => $validated['destination_account_id'],
                'amount' => $validated['amount'],
                'description' => $validated['description'],
                'date' => $validated['date'],
                'notes' => $validated['notes'] ?? null,
            ]);
        } else {
            // Other types (adjustment, etc) not supported in basic UI yet
            abort(400, 'Unsupported transaction type');
        }

        return redirect()->route('transactions.index')->with('success', 'Transaction created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Transaction $transaction): Response
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(403);
        }

        $transaction->load(['category', 'entries.account']);

        return Inertia::render('Transactions/Show', [
            'transaction' => $transaction,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, Transaction $transaction): Response
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(403);
        }

        $transaction->load('entries');

        $accounts = Account::where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $categories = Category::where('user_id', $request->user()->id)
            ->orderBy('name')
            ->get();
            
        // Map data for the form based on entries
        $formData = [
            'id' => $transaction->id,
            'type' => $transaction->type->value,
            'amount' => $transaction->amount,
            'date' => $transaction->date->format('Y-m-d'),
            'description' => $transaction->description,
            'category_id' => $transaction->category_id,
            'notes' => $transaction->notes,
        ];
        
        if ($transaction->type === TransactionType::TRANSFER) {
            $source = $transaction->entries->firstWhere('entry_type', EntryType::DEBIT); // Debits decrease source
            $dest = $transaction->entries->firstWhere('entry_type', EntryType::CREDIT);
            $formData['source_account_id'] = $source ? $source->account_id : null;
            $formData['destination_account_id'] = $dest ? $dest->account_id : null;
        } else {
            $entry = $transaction->entries->firstWhere('account_id', '!=', null);
            $formData['account_id'] = $entry ? $entry->account_id : null;
        }

        return Inertia::render('Transactions/Edit', [
            'transaction' => $formData,
            'accounts' => $accounts,
            'categories' => $categories,
            'transactionTypes' => TransactionType::cases(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Transaction $transaction): RedirectResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'type' => ['required', Rule::enum(TransactionType::class)],
            'amount' => ['required', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'notes' => ['nullable', 'string'],
            
            // For income/expense
            'account_id' => ['required_unless:type,transfer', 'exists:accounts,id'],
            
            // For transfer
            'source_account_id' => ['required_if:type,transfer', 'exists:accounts,id'],
            'destination_account_id' => ['required_if:type,transfer', 'exists:accounts,id', 'different:source_account_id'],
        ]);

        $this->transactionService->updateTransaction($transaction, $validated);

        return redirect()->route('transactions.index')->with('success', 'Transaction updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Transaction $transaction): RedirectResponse
    {
        if ($transaction->user_id !== $request->user()->id) {
            abort(403);
        }
        
        $this->transactionService->deleteTransaction($transaction);

        return redirect()->route('transactions.index')->with('success', 'Transaction deleted successfully.');
    }
}
