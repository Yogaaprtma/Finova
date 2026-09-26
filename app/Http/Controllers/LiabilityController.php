<?php

namespace App\Http\Controllers;

use App\Enums\LiabilityType;
use App\Models\Account;
use App\Models\Liability;
use App\Services\LiabilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class LiabilityController extends Controller
{
    public function __construct(private LiabilityService $liabilityService) {}

    /**
     * Display a listing of liabilities and overview metrics.
     */
    public function index(Request $request): Response
    {
        $overview = $this->liabilityService->getLiabilitiesOverview($request->user()->id);

        return Inertia::render('Liabilities/Index', [
            'overview' => $overview,
        ]);
    }

    /**
     * Show the form for creating a new liability.
     */
    public function create(Request $request): Response
    {
        $types = array_map(function (LiabilityType $type) {
            return [
                'value' => $type->value,
                'label' => $type->label(),
                'color' => $type->color(),
                'description' => $type->description(),
            ];
        }, LiabilityType::cases());

        $accounts = Account::where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'current_balance']);

        return Inertia::render('Liabilities/Create', [
            'liabilityTypes' => $types,
            'accounts' => $accounts,
        ]);
    }

    /**
     * Store a newly created liability in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('liabilities')
                    ->where(fn ($query) => $query->where('user_id', $request->user()->id))
                    ->whereNull('deleted_at'),
            ],
            'type' => ['required', Rule::enum(LiabilityType::class)],
            'currency' => ['nullable', 'string', 'size:3'],
            'initial_balance' => ['required', 'numeric', 'min:0'],
            'current_balance' => ['nullable', 'numeric', 'min:0'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'minimum_payment' => ['nullable', 'numeric', 'min:0'],
            'due_date_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'remaining_terms' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['user_id'] = $request->user()->id;

        $liability = $this->liabilityService->createLiability($validated);

        return redirect()->route('liabilities.show', $liability->id)
            ->with('status', 'Liability successfully added.');
    }

    /**
     * Display the specified liability.
     */
    public function show(Request $request, string $id): Response
    {
        $liability = Liability::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with(['transactions' => function ($query) {
                $query->orderBy('created_at', 'desc')->with('transaction');
            }])
            ->firstOrFail();

        $accounts = Account::where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'current_balance']);

        return Inertia::render('Liabilities/Show', [
            'liability' => $liability,
            'accounts' => $accounts,
        ]);
    }

    /**
     * Show the form for editing the specified liability.
     */
    public function edit(Request $request, string $id): Response
    {
        $liability = Liability::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return Inertia::render('Liabilities/Edit', [
            'liability' => $liability,
        ]);
    }

    /**
     * Update the specified liability in storage.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $liability = Liability::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('liabilities')
                    ->where(fn ($query) => $query->where('user_id', $request->user()->id))
                    ->ignore($liability->id)
                    ->whereNull('deleted_at'),
            ],
            'currency' => ['nullable', 'string', 'size:3'],
            'current_balance' => ['nullable', 'numeric', 'min:0'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'interest_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'minimum_payment' => ['nullable', 'numeric', 'min:0'],
            'due_date_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'remaining_terms' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->liabilityService->updateLiability($liability, $validated);

        return redirect()->route('liabilities.show', $liability->id)
            ->with('status', 'Liability successfully updated.');
    }

    /**
     * Remove the specified liability from storage.
     */
    public function destroy(Request $request, string $id): RedirectResponse
    {
        $liability = Liability::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $this->liabilityService->deleteLiability($liability);

        return redirect()->route('liabilities.index')
            ->with('status', 'Liability successfully deleted.');
    }

    /**
     * Record a payment towards the liability.
     */
    public function pay(Request $request, string $id): RedirectResponse
    {
        $liability = Liability::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'source_account_id' => [
                'required',
                'string',
                Rule::exists('accounts', 'id')->where('user_id', $request->user()->id),
            ],
            'amount' => ['required', 'numeric', 'min:1'],
            'date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['liability_id'] = $liability->id;
        $validated['user_id'] = $request->user()->id;

        $this->liabilityService->recordPayment($validated);

        return redirect()->route('liabilities.show', $liability->id)
            ->with('status', 'Payment successfully recorded.');
    }

    /**
     * Record an increase or charge on the liability.
     */
    public function increase(Request $request, string $id): RedirectResponse
    {
        $liability = Liability::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'destination_account_id' => [
                'nullable',
                'string',
                Rule::exists('accounts', 'id')->where('user_id', $request->user()->id),
            ],
            'date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['liability_id'] = $liability->id;
        $validated['user_id'] = $request->user()->id;

        $this->liabilityService->recordIncrease($validated);

        return redirect()->route('liabilities.show', $liability->id)
            ->with('status', 'Charge / Loan increase recorded successfully.');
    }
}

