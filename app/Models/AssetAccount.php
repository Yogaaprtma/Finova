<?php

namespace App\Models;

use App\Enums\AssetType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssetAccount extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id', 'name', 'type', 'platform', 'ticker', 'currency',
        'quantity', 'avg_purchase_price', 'current_price', 'manual_value',
        'last_price_update', 'is_active', 'sort_order', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => AssetType::class,
            'quantity' => 'decimal:8',
            'avg_purchase_price' => 'decimal:8',
            'current_price' => 'decimal:8',
            'last_price_update' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(AssetTransaction::class);
    }
}
