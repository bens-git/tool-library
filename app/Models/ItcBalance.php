<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ITC Balance Model - tracks user credit balances
 * 
 * @property int $id
 * @property int $user_id
 * @property float $balance
 * @property float $lifetime_earned
 * @property float $lifetime_spent
 * @property \Carbon\Carbon|null $last_decay_at
 */
class ItcBalance extends Model
{
    protected $fillable = [
        'user_id',
        'balance',
        'lifetime_earned',
        'lifetime_spent',
        'last_decay_at',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'lifetime_earned' => 'decimal:2',
        'lifetime_spent' => 'decimal:2',
        'last_decay_at' => 'datetime',
    ];

    /**
     * Get the user that owns this balance
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all ledger transactions for this balance
     */
    public function ledgers()
    {
        return $this->hasMany(ItcLedger::class);
    }

    /**
     * Check if user has sufficient credits
     */
    public function hasEnough(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    /**
     * Get formatted balance
     */
    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->balance, 2) . ' ITC';
    }
}

