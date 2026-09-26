<?php

namespace App\Http\Controllers;

use App\Enums\AssetType;
use App\Models\Account;
use App\Models\AssetAccount;
use App\Services\AssetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AssetController extends Controller
{
    public function __construct(private AssetService $assetService) {}

    /**
     * Display a listing of the assets and portfolio overview.
     */
    public function index(Request $request): Response
    {
        $overview = $this->assetService->getPortfolioOverview($request->user()->id);

        return Inertia::render('Assets/Index', [
            'overview' => $overview,
        ]);
    }

    /**
     * Show the form for creating a new asset account.
     */
    public function create(Request $request): Response
    {
        $types = array_map(function (AssetType $type) {
            return [
                'value' => $type->value,
                'label' => $type->label(),
                'color' => $type->color(),
                'is_investment' => $type->isInvestment(),
                'is_valued_manually' => $type->isValuedManually(),
                'precision' => $type->quantityPrecision(),
            ];
        }, AssetType::cases());

        $accounts = Account::where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'current_balance']);

        return Inertia::render('Assets/Create', [
            'assetTypes' => $types,
            'accounts' => $accounts,
        ]);
    }

    /**
     * Store a newly created asset account in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('asset_accounts')
                    ->where(fn ($query) => $query->where('user_id', $request->user()->id))
                    ->whereNull('deleted_at'),
            ],
            'type' => ['required', Rule::enum(AssetType::class)],
            'platform' => ['nullable', 'string', 'max:100'],
            'ticker' => ['nullable', 'string', 'max:20'],
            'currency' => ['nullable', 'string', 'size:3'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'avg_purchase_price' => ['nullable', 'numeric', 'min:0'],
            'current_price' => ['nullable', 'numeric', 'min:0'],
            'manual_value' => ['nullable', 'numeric', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['user_id'] = $request->user()->id;

        $asset = $this->assetService->createAsset($validated);

        return redirect()->route('assets.show', $asset->id)
            ->with('status', 'Asset successfully added to portfolio.');
    }

    /**
     * Display the specified asset account.
     */
    public function show(Request $request, string $id): Response
    {
        $asset = AssetAccount::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with(['transactions' => function ($query) {
                $query->orderBy('created_at', 'desc')->with('transaction');
            }])
            ->firstOrFail();

        $accounts = Account::where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'current_balance']);

        return Inertia::render('Assets/Show', [
            'asset' => $asset,
            'accounts' => $accounts,
        ]);
    }

    /**
     * Show the form for editing the specified asset.
     */
    public function edit(Request $request, string $id): Response
    {
        $asset = AssetAccount::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return Inertia::render('Assets/Edit', [
            'asset' => $asset,
        ]);
    }

    /**
     * Update the specified asset in storage.
     */
    public function update(Request $request, string $id): RedirectResponse
    {
        $asset = AssetAccount::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('asset_accounts')
                    ->where(fn ($query) => $query->where('user_id', $request->user()->id))
                    ->ignore($asset->id)
                    ->whereNull('deleted_at'),
            ],
            'platform' => ['nullable', 'string', 'max:100'],
            'ticker' => ['nullable', 'string', 'max:20'],
            'currency' => ['nullable', 'string', 'size:3'],
            'manual_value' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->assetService->updateAsset($asset, $validated);

        return redirect()->route('assets.show', $asset->id)
            ->with('status', 'Asset details updated.');
    }

    /**
     * Fast update for current market price.
     */
    public function updatePrice(Request $request, string $id): RedirectResponse
    {
        $asset = AssetAccount::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'current_price' => ['required', 'numeric', 'min:0'],
        ]);

        $this->assetService->updateCurrentPrice($asset, (float) $validated['current_price']);

        return back()->with('status', 'Market price updated successfully.');
    }

    /**
     * Record a purchase transaction for this asset.
     */
    public function buy(Request $request, string $id): RedirectResponse
    {
        $asset = AssetAccount::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $validated = $request->validate([
            'source_account_id' => [
                'required',
                Rule::exists('accounts', 'id')->where(fn ($q) => $q->where('user_id', $request->user()->id)->where('is_active', true)),
            ],
            'quantity' => ['required', 'numeric', 'min:0.00000001'],
            'price_per_unit' => ['required', 'numeric', 'min:0.01'],
            'fees' => ['nullable', 'integer', 'min:0'],
            'date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['asset_account_id'] = $asset->id;

        $this->assetService->recordPurchase($validated);

        return back()->with('status', 'Purchase transaction recorded successfully.');
    }

    /**
     * Record a sale transaction for this asset.
     */
    public function sell(Request $request, string $id): RedirectResponse
    {
        $asset = AssetAccount::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $maxQty = (float) $asset->quantity;

        $validated = $request->validate([
            'destination_account_id' => [
                'required',
                Rule::exists('accounts', 'id')->where(fn ($q) => $q->where('user_id', $request->user()->id)->where('is_active', true)),
            ],
            'quantity' => ['required', 'numeric', 'min:0.00000001', "max:{$maxQty}"],
            'price_per_unit' => ['required', 'numeric', 'min:0.01'],
            'fees' => ['nullable', 'integer', 'min:0'],
            'date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['asset_account_id'] = $asset->id;

        $this->assetService->recordSale($validated);

        return back()->with('status', 'Sale transaction recorded successfully.');
    }

    /**
     * Remove the specified asset from portfolio.
     */
    public function destroy(Request $request, string $id): RedirectResponse
    {
        $asset = AssetAccount::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $this->assetService->deleteAsset($asset);

        return redirect()->route('assets.index')
            ->with('status', 'Asset removed from portfolio.');
    }
}
