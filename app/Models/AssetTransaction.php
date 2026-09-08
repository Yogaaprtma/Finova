<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetTransaction extends Model
{
    use HasFactory, HasUuids;
    
    public $timestamps = false;

    protected $fillable = [
        'asset_account_id', 'transaction_id', 'type', 'quantity', 'price_per_unit',
        'total_amount', 'fees', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:8',
            'price_per_unit' => 'decimal:8',
            'created_at' => 'datetime',
        ];
    }

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(AssetAccount::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
