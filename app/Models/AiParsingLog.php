<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiParsingLog extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'transaction_id',
        'source',
        'raw_message',
        'parsed_data',
        'confidence',
        'ai_provider',
        'ai_model',
        'prompt_version',
        'status',
        'response_time_ms',
        'error_message',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'parsed_data' => 'array',
            'confidence' => 'float',
            'response_time_ms' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}

