<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ITC Ledger Model - tracks all credit transactions
 * 
 * @property int $id
 * @property int $user_id
 * @property int|null $item_id
 * @property int|null $rental_id
 * @property string $type (earned, spent, decay, bonus, penalty)
 * @property string $category (lending, borrowing, maintenance, admin, voting_bonus)
 * @property float $amount
 * @property float $balance_after
 * @property string|null $description
 * @property array|null $metadata
 */
class ItcLedger extends Model
{
    protected $fillable = [
        'user_id',
        'item_id',
        'rental_id',
        'type',
        'category',
        'amount',
        'balance_after',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Transaction types
    public const TYPE_EARNED = 'earned';
    public const TYPE_SPENT = 'spent';
    public const TYPE_DECAY = 'decay';
    public const TYPE_BONUS = 'bonus';
    public const TYPE_PENALTY = 'penalty';

    // Transaction categories
    public const CATEGORY_LENDING = 'lending';
    public const CATEGORY_BORROWING = 'borrowing';
    public const CATEGORY_MAINTENANCE = 'maintenance';
    public const CATEGORY_ADMIN = 'admin';
    public const CATEGORY_VOTING_BONUS = 'voting_bonus';

    /**
     * Get the user that made this transaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related item (if any)
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Get the related rental (if any)
     */
    public function rental(): BelongsTo
    {
        return $this->belongsTo(Rental::class);
    }

    /**
     * Check if this is a credit (positive amount)
     */
    public function isCredit(): bool
    {
        return $this->amount > 0;
    }

    /**
     * Check if this is a debit (negative amount)
     */
    public function isDebit(): bool
    {
        return $this->amount < 0;
    }

    /**
     * Get formatted amount
     */
    public function getFormattedAmountAttribute(): string
    {
        $prefix = $this->amount >= 0 ? '+' : '';
        return $prefix . number_format($this->amount, 2) . ' ITC';
    }
}

