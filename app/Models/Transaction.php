<?php

namespace App\Models;

use App\Enums\TransactionSource;
use App\Enums\TransactionType;
use App\Traits\Auditable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $id
 * @property int $user_id
 * @property string|null $category_id
 * @property TransactionType $type
 * @property CarbonInterface $date
 * @property int $amount
 * @property string $currency
 * @property string $description
 * @property string|null $notes
 * @property string|null $reference_number
 * @property TransactionSource $source
 * @property bool $is_confirmed
 * @property-read Collection<int, TransactionEntry> $entries
 */
class Transaction extends Model
{
    use Auditable, HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id', 'category_id', 'type', 'date', 'amount', 'currency',
        'description', 'notes', 'reference_number', 'source', 'is_confirmed',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'source' => TransactionSource::class,
            'date' => 'date',
            'is_confirmed' => 'boolean',
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

    public function entries(): HasMany
    {
        return $this->hasMany(TransactionEntry::class);
    }

    public function assetTransaction(): HasOne
    {
        return $this->hasOne(AssetTransaction::class);
    }

    public function liabilityTransaction(): HasOne
    {
        return $this->hasOne(LiabilityTransaction::class);
    }
}
