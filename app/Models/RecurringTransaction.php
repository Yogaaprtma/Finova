<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property int $user_id
 * @property string|null $category_id
 * @property string $account_id
 * @property string $name
 * @property string $type
 * @property int $amount
 * @property string $currency
 * @property string $description
 * @property string $frequency
 * @property int|null $day_of_month
 * @property int|null $day_of_week
 * @property CarbonInterface $start_date
 * @property CarbonInterface|null $end_date
 * @property bool $is_active
 * @property bool $auto_confirm
 * @property CarbonInterface|null $last_generated_date
 */
class RecurringTransaction extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'category_id',
        'account_id',
        'name',
        'type',
        'amount',
        'currency',
        'description',
        'frequency',
        'day_of_month',
        'day_of_week',
        'start_date',
        'end_date',
        'is_active',
        'auto_confirm',
        'last_generated_date',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'is_active' => 'boolean',
            'auto_confirm' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'date',
            'last_generated_date' => 'date',
            'day_of_month' => 'integer',
            'day_of_week' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Determine if this recurring transaction should be generated on the given date (default today).
     */
    public function shouldGenerateOn(?CarbonInterface $targetDate = null): bool
    {
        $target = $targetDate ? Carbon::parse($targetDate)->startOfDay() : Carbon::today();

        if (! $this->is_active) {
            return false;
        }

        $startDate = Carbon::parse($this->start_date)->startOfDay();
        if ($target->lt($startDate)) {
            return false;
        }

        if ($this->end_date) {
            $endDate = Carbon::parse($this->end_date)->startOfDay();
            if ($target->gt($endDate)) {
                return false;
            }
        }

        if ($this->last_generated_date) {
            $lastGen = Carbon::parse($this->last_generated_date)->startOfDay();
            if ($lastGen->gte($target)) {
                return false;
            }
        }

        return match ($this->frequency) {
            'daily' => true,
            'weekly' => $this->day_of_week !== null
                ? $target->dayOfWeek === (int) $this->day_of_week
                : $target->diffInDays($startDate) % 7 === 0,
            'biweekly' => $target->diffInDays($startDate) % 14 === 0,
            'monthly' => $this->day_of_month !== null
                ? $target->day === (int) $this->day_of_month
                : $target->day === $startDate->day,
            'yearly' => $target->month === $startDate->month && $target->day === $startDate->day,
            default => false,
        };
    }

    /**
     * Calculate next due date after a given reference date.
     */
    public function getNextDueDate(?CarbonInterface $fromDate = null): ?CarbonInterface
    {
        $ref = $fromDate ? Carbon::parse($fromDate)->startOfDay() : Carbon::today();
        $startDate = Carbon::parse($this->start_date)->startOfDay();

        // If reference is before start date, next is start date or first match
        $current = $ref->gt($startDate) ? $ref->copy() : $startDate->copy();

        // If today already generated, start checking from tomorrow
        if ($this->last_generated_date && Carbon::parse($this->last_generated_date)->startOfDay()->gte($current)) {
            $current->addDay();
        }

        // Loop up to 366 days to find the next valid occurrence
        for ($i = 0; $i <= 366; $i++) {
            if ($this->end_date && $current->gt(Carbon::parse($this->end_date)->startOfDay())) {
                return null;
            }

            $isMatch = match ($this->frequency) {
                'daily' => true,
                'weekly' => $this->day_of_week !== null
                    ? $current->dayOfWeek === (int) $this->day_of_week
                    : $current->diffInDays($startDate) % 7 === 0,
                'biweekly' => $current->diffInDays($startDate) % 14 === 0,
                'monthly' => $this->day_of_month !== null
                    ? $current->day === (int) $this->day_of_month
                    : $current->day === $startDate->day,
                'yearly' => $current->month === $startDate->month && $current->day === $startDate->day,
                default => false,
            };

            if ($isMatch) {
                return $current;
            }

            $current->addDay();
        }

        return null;
    }
}
