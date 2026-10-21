<?php

namespace App\Models;

use App\Enums\EntryType;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $transaction_id
 * @property string|null $account_id
 * @property string|null $asset_account_id
 * @property string|null $liability_id
 * @property EntryType $entry_type
 * @property int $amount
 * @property CarbonInterface $created_at
 * @property-read Account|null $account
 */
class TransactionEntry extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'transaction_id', 'account_id', 'asset_account_id', 'liability_id',
        'entry_type', 'amount', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'entry_type' => EntryType::class,
            'created_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(AssetAccount::class);
    }

    public function liability(): BelongsTo
    {
        return $this->belongsTo(Liability::class);
    }
}
