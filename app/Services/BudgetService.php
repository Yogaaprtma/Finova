<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BudgetService
{
    /**
     * Get all budgets for a user ordered by period.
     *
     * @return Collection<int, Budget>
     */
    public function getBudgetsForUser(User|int|string $user): Collection
    {
        $userId = $user instanceof User ? $user->id : $user;

        return Budget::with(['items.category'])
            ->where('user_id', $userId)
            ->orderBy('period_start', 'desc')
            ->get();
    }

    /**
     * Calculate comprehensive spending progress for a budget and its items.
     *
     * @return array<string, mixed>
     */
    public function getBudgetWithProgress(Budget $budget): array
    {
        $budget->loadMissing(['items.category']);

        $startDate = Carbon::parse($budget->period_start)->format('Y-m-d');
        $endDate = Carbon::parse($budget->period_end)->format('Y-m-d');

        // Fetch total expense per category in period in a single query
        $categoryIds = $budget->items->pluck('category_id')->filter()->all();

        $spentByCategory = Transaction::where('user_id', $budget->user_id)
            ->where('type', 'expense')
            ->whereIn('category_id', $categoryIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('category_id')
            ->selectRaw('category_id, SUM(amount) as total_spent')
            ->pluck('total_spent', 'category_id')
            ->all();

        $itemsProgress = [];
        $totalItemsBudget = 0;
        $totalItemsSpent = 0;
        $overBudgetCount = 0;

        foreach ($budget->items as $item) {
            $catId = (string) $item->category_id;
            $spent = (int) ($spentByCategory[$catId] ?? 0);
            $limit = (int) $item->amount;
            $remaining = max(0, $limit - $spent);
            $overBudget = max(0, $spent - $limit);

            $percentage = $limit > 0 ? round(($spent / $limit) * 100, 1) : 0;

            if ($spent > $limit) {
                $status = 'danger'; // Over budget
                $overBudgetCount++;
            } elseif ($percentage >= 90) {
                $status = 'danger'; // Near limit
            } elseif ($percentage >= 75) {
                $status = 'warning'; // Caution
            } else {
                $status = 'normal'; // Safe
            }

            $totalItemsBudget += $limit;
            $totalItemsSpent += $spent;

            $itemsProgress[] = [
                'id' => $item->id,
                'category_id' => $item->category_id,
                'category' => $item->category ? [
                    'id' => $item->category->id,
                    'name' => $item->category->name,
                    'icon' => $item->category->icon,
                    'color' => $item->category->color,
                ] : null,
                'limit' => $limit,
                'spent' => $spent,
                'remaining' => $remaining,
                'over_budget' => $overBudget,
                'percentage' => $percentage,
                'status' => $status,
            ];
        }

        $overallLimit = (int) ($budget->total_budget ?: $totalItemsBudget);
        $overallSpent = $totalItemsSpent;
        $overallRemaining = max(0, $overallLimit - $overallSpent);
        $overallOverBudget = max(0, $overallSpent - $overallLimit);
        $overallPercentage = $overallLimit > 0 ? round(($overallSpent / $overallLimit) * 100, 1) : 0;

        if ($overallSpent > $overallLimit) {
            $overallStatus = 'danger';
        } elseif ($overallPercentage >= 90) {
            $overallStatus = 'danger';
        } elseif ($overallPercentage >= 75) {
            $overallStatus = 'warning';
        } else {
            $overallStatus = 'normal';
        }

        return [
            'id' => $budget->id,
            'name' => $budget->name,
            'period_start' => $startDate,
            'period_end' => $endDate,
            'total_budget' => $overallLimit,
            'total_spent' => $overallSpent,
            'total_remaining' => $overallRemaining,
            'total_over_budget' => $overallOverBudget,
            'percentage' => $overallPercentage,
            'status' => $overallStatus,
            'over_budget_count' => $overBudgetCount,
            'items' => $itemsProgress,
            'created_at' => $budget->created_at?->toISOString(),
            'updated_at' => $budget->updated_at?->toISOString(),
        ];
    }

    /**
     * Get active budget for the given date (default today).
     */
    public function getCurrentBudgetForUser(User|int|string $user, ?CarbonInterface $date = null): ?array
    {
        $userId = $user instanceof User ? $user->id : $user;
        $target = $date ? Carbon::parse($date)->format('Y-m-d') : Carbon::today()->format('Y-m-d');

        $budget = Budget::where('user_id', $userId)
            ->where('period_start', '<=', $target)
            ->where('period_end', '>=', $target)
            ->latest('period_start')
            ->first();

        if (! $budget) {
            return null;
        }

        return $this->getBudgetWithProgress($budget);
    }

    /**
     * Create a new budget along with budget items.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array{category_id: string, amount: int|float|string}>  $items
     */
    public function createBudget(User|int|string $user, array $data, array $items = []): Budget
    {
        $userId = $user instanceof User ? $user->id : $user;

        return DB::transaction(function () use ($userId, $data, $items) {
            $budget = Budget::create([
                'user_id' => $userId,
                'name' => $data['name'],
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'total_budget' => isset($data['total_budget']) && $data['total_budget'] !== '' ? (int) $data['total_budget'] : null,
            ]);

            foreach ($items as $item) {
                if (! empty($item['category_id']) && isset($item['amount']) && (int) $item['amount'] > 0) {
                    BudgetItem::create([
                        'budget_id' => $budget->id,
                        'category_id' => $item['category_id'],
                        'amount' => (int) $item['amount'],
                    ]);
                }
            }

            return $budget->fresh(['items.category']);
        });
    }

    /**
     * Update an existing budget and sync budget items.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array{category_id: string, amount: int|float|string}>  $items
     */
    public function updateBudget(Budget $budget, array $data, array $items = []): Budget
    {
        return DB::transaction(function () use ($budget, $data, $items) {
            $budget->update([
                'name' => $data['name'] ?? $budget->name,
                'period_start' => $data['period_start'] ?? $budget->period_start,
                'period_end' => $data['period_end'] ?? $budget->period_end,
                'total_budget' => array_key_exists('total_budget', $data)
                    ? ($data['total_budget'] !== null && $data['total_budget'] !== '' ? (int) $data['total_budget'] : null)
                    : $budget->total_budget,
            ]);

            // Replace items
            BudgetItem::where('budget_id', $budget->id)->delete();

            foreach ($items as $item) {
                if (! empty($item['category_id']) && isset($item['amount']) && (int) $item['amount'] > 0) {
                    BudgetItem::create([
                        'budget_id' => $budget->id,
                        'category_id' => $item['category_id'],
                        'amount' => (int) $item['amount'],
                    ]);
                }
            }

            return $budget->fresh(['items.category']);
        });
    }

    /**
     * Delete a budget.
     */
    public function deleteBudget(Budget $budget): bool
    {
        return (bool) $budget->delete();
    }
}
