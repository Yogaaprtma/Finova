<?php

namespace App\Models;

use App\Enums\LiabilityType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Liability extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id', 'name', 'type', 'currency', 'initial_balance', 'current_balance',
        'credit_limit', 'interest_rate', 'minimum_payment', 'due_date_day',
        'start_date', 'end_date', 'remaining_terms', 'is_active', 'sort_order', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'type' => LiabilityType::class,
            'interest_rate' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LiabilityTransaction::class);
    }
}
