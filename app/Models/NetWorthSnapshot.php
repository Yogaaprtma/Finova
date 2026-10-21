<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property int $user_id
 * @property CarbonInterface $date
 * @property int $total_cash
 * @property int $total_investments
 * @property int $total_liabilities
 * @property int $net_worth
 * @property CarbonInterface $created_at
 */
class NetWorthSnapshot extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'date', 'total_cash', 'total_investments',
        'total_liabilities', 'net_worth', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
