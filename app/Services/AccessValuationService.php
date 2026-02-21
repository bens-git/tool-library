<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemAccessValue;
use Illuminate\Support\Facades\Log;

/**
 * Access Valuation Service - calculates item credit values
 * 
 * This service handles:
 * - Calculating base credit values from purchase prices
 * - Managing item value decay over time
 * - Voting-based value adjustments
 * - Integration with future Integral system
 */
class AccessValuationService
{
    // Base formula: 1 credit per $100 of purchase value per day
    public const BASE_VALUE_PER_DOLLAR = 0.01;
    
    // Minimum/maximum rates
    public const MIN_DAILY_RATE = 0.1;
    public const MAX_DAILY_RATE = 10.0;
    
    // Decay settings
    public const DEFAULT_DECAY_RATE = 0.0001; // 0.01% per day
    public const MAX_DECAY = 0.9; // Max 90% decay
    public const MIN_DECAYED_RATE = 0.1; // Minimum 10% of original
    
    /**
     * Calculate base credit value from purchase price
     * Formula: purchase_value * BASE_VALUE_PER_DOLLAR
     * Example: $100 = 1 credit/day, $500 = 5 credits/day
     */
    public function calculateBaseValue(?float $purchaseValue): float
    {
        if (!$purchaseValue || $purchaseValue <= 0) {
            return ItemAccessValue::DEFAULT_DAILY_RATE;
        }
        
        $value = $purchaseValue * self::BASE_VALUE_PER_DOLLAR;
        
        // Clamp to min/max
        return round(max(self::MIN_DAILY_RATE, min(self::MAX_DAILY_RATE, $value)), 2);
    }

    /**
     * Calculate weekly rate from daily rate
     */
    public function calculateWeeklyRate(float $dailyRate): float
    {
        return round($dailyRate * 5, 2);
    }

    /**
     * Create or update access value for an item
     */
    public function setAccessValue(Item $item, ?float $purchaseValue = null): ItemAccessValue
    {
        $purchaseValue = $purchaseValue ?? $item->purchase_value;
        $baseValue = $this->calculateBaseValue($purchaseValue);
        
        $accessValue = ItemAccessValue::firstOrNew(['item_id' => $item->id]);
        
        $accessValue->purchase_value = $purchaseValue;
        $accessValue->base_credit_value = $baseValue;
        
        // Only set initial rates if not already set
        if (!$accessValue->exists) {
            $accessValue->current_daily_rate = $baseValue;
            $accessValue->current_weekly_rate = $this->calculateWeeklyRate($baseValue);
            $accessValue->decay_rate = self::DEFAULT_DECAY_RATE;
            $accessValue->last_rate_change = now();
        }
        
        $accessValue->save();
        
        return $accessValue;
    }

    /**
     * Calculate decayed daily rate based on time since last rate change
     * Items that have been available longer cost less (encourages sharing)
     */
    public function calculateDecayedRate(ItemAccessValue $accessValue): float
    {
        if (!$accessValue->last_rate_change) {
            return $accessValue->current_daily_rate;
        }
        
        $daysSinceChange = $accessValue->last_rate_change->diffInDays(now());
        
        // Calculate decay
        $decayFactor = 1 - ($accessValue->decay_rate * $daysSinceChange);
        $decayFactor = max(self::MIN_DECAYED_RATE, min(1, $decayFactor));
        
        return round($accessValue->current_daily_rate * $decayFactor, 2);
    }

    /**
     * Apply a community vote to an item's credit rate
     * Uses weighted voting where users with more credits have more influence
     */
    public function applyVote(ItemAccessValue $accessValue, float $voteValue, float $userBalance): float
    {
        // Clamp vote value
        $voteValue = max(self::MIN_DAILY_RATE, min(self::MAX_DAILY_RATE, $voteValue));
        
        // Calculate weight based on user's balance (optional: could use different weighting)
        $weight = 1; // Equal weight for now
        
        // Blend new vote with current rate
        // New rate = (current_rate * (count - 1) + new_vote) / count
        $newCount = $accessValue->vote_count + 1;
        $weightedSum = ($accessValue->current_daily_rate * $accessValue->vote_count) + ($voteValue * $weight);
        $newRate = $weightedSum / ($accessValue->vote_count + $weight);
        
        // Clamp result
        $newRate = max(self::MIN_DAILY_RATE, min(self::MAX_DAILY_RATE, $newRate));
        
        // Update access value
        $accessValue->vote_count = $newCount;
        $accessValue->vote_total += $voteValue;
        $accessValue->current_daily_rate = round($newRate, 2);
        $accessValue->current_weekly_rate = $this->calculateWeeklyRate($newRate);
        $accessValue->last_rate_change = now();
        $accessValue->save();
        
        return $accessValue->current_daily_rate;
    }

    /**
     * Get the effective rate for an item (considering decay)
     */
    public function getEffectiveRate(Item $item): float
    {
        $accessValue = ItemAccessValue::where('item_id', $item->id)->first();
        
        if (!$accessValue) {
            // Try to create from item's purchase value
            $accessValue = $this->setAccessValue($item);
        }
        
        return $this->calculateDecayedRate($accessValue);
    }

    /**
     * Calculate rental cost for an item
     */
    public function calculateRentalCost(Item $item, int $days): float
    {
        $rate = $this->getEffectiveRate($item);
        return round($rate * $days, 2);
    }

    /**
     * Bulk create access values for all items without one
     */
    public function createMissingAccessValues(): int
    {
        $items = Item::whereDoesntHave('accessValue')->get();
        $count = 0;
        
        foreach ($items as $item) {
            $this->setAccessValue($item);
            $count++;
        }
        
        Log::info("Created {$count} missing access values");
        
        return $count;
    }

    /**
     * Get summary statistics for all items
     */
    public function getStatistics(): array
    {
        $totalItems = ItemAccessValue::count();
        $avgRate = ItemAccessValue::avg('current_daily_rate') ?? 0;
        $totalVotes = ItemAccessValue::sum('vote_count');
        
        return [
            'total_items' => $totalItems,
            'average_daily_rate' => round($avgRate, 2),
            'total_votes' => $totalVotes,
            'min_rate' => ItemAccessValue::min('current_daily_rate') ?? 0,
            'max_rate' => ItemAccessValue::max('current_daily_rate') ?? 0,
        ];
    }
}

