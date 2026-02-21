<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Item Access Value Model - credit cost for each item
 * 
 * @property int $id
 * @property int $item_id
 * @property float|null $purchase_value
 * @property float|null $base_credit_value
 * @property float $current_daily_rate
 * @property float $current_weekly_rate
 * @property int $vote_count
 * @property float $vote_total
 * @property float $average_vote
 * @property float $decay_rate
 * @property \Carbon\Carbon|null $last_rate_change
 */
class ItemAccessValue extends Model
{
    protected $fillable = [
        'item_id',
        'purchase_value',
        'base_credit_value',
        'current_daily_rate',
        'current_weekly_rate',
        'vote_count',
        'vote_total',
        'average_vote',
        'decay_rate',
        'last_rate_change',
    ];

    protected $casts = [
        'purchase_value' => 'decimal:2',
        'base_credit_value' => 'decimal:2',
        'current_daily_rate' => 'decimal:2',
        'current_weekly_rate' => 'decimal:2',
        'vote_count' => 'integer',
        'vote_total' => 'decimal:2',
        'average_vote' => 'decimal:2',
        'decay_rate' => 'decimal:5',
        'last_rate_change' => 'datetime',
    ];

    // Default rates
    public const DEFAULT_DAILY_RATE = 1.0;
    public const DEFAULT_WEEKLY_RATE = 5.0;

    // Decay formula: 1 credit per $100 of purchase value per day (simple formula)
    public const BASE_VALUE_MULTIPLIER = 0.01; // 1% of purchase value per day

    /**
     * Get the item this access value belongs to
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * Calculate base credit value from purchase price
     * Formula: purchase_value * BASE_VALUE_MULTIPLIER (e.g., $100 = 1 credit/day)
     */
    public function calculateBaseValue(): float
    {
        if (!$this->purchase_value) {
            return self::DEFAULT_DAILY_RATE;
        }

        return round($this->purchase_value * self::BASE_VALUE_MULTIPLIER, 2);
    }

    /**
     * Calculate the daily rate after applying decay
     * The longer an item is available, the less it costs
     */
    public function calculateDecayedDailyRate(): float
    {
        if (!$this->last_rate_change) {
            return $this->current_daily_rate;
        }

        $daysSinceChange = $this->last_rate_change->diffInDays(now());
        $decayFactor = 1 - ($this->decay_rate * $daysSinceChange);
        $decayFactor = max($decayFactor, 0.1); // Minimum 10% of original value

        return round($this->current_daily_rate * $decayFactor, 2);
    }

    /**
     * Update the average vote from vote data
     */
    public function updateVoteAverage(): void
    {
        if ($this->vote_count > 0) {
            $this->average_vote = round($this->vote_total / $this->vote_count, 2);
        }
    }

    /**
     * Add a vote and recalculate rates
     */
    public function addVote(float $voteValue): void
    {
        $this->vote_count++;
        $this->vote_total += $voteValue;
        $this->updateVoteAverage();
        
        // Blend vote with current rate (70% current, 30% new vote)
        $newRate = ($this->current_daily_rate * 0.7) + ($voteValue * 0.3);
        $this->current_daily_rate = round($newRate, 2);
        $this->current_weekly_rate = round($newRate * 5, 2);
        $this->last_rate_change = now();
        
        $this->save();
    }

    /**
     * Get formatted daily rate
     */
    public function getFormattedDailyRateAttribute(): string
    {
        return number_format($this->current_daily_rate, 2) . ' ITC/day';
    }

    /**
     * Get formatted weekly rate
     */
    public function getFormattedWeeklyRateAttribute(): string
    {
        return number_format($this->current_weekly_rate, 2) . ' ITC/week';
    }
}

