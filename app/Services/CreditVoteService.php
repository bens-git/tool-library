<?php

namespace App\Services;

use App\Models\CreditVote;
use App\Models\Item;
use App\Models\ItemAccessValue;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Credit Vote Service - handles community voting on credit rates
 * 
 * This service manages:
 * - Recording user votes on item credit rates
 * - Calculating weighted votes
 * - Applying vote results to item access values
 * - Voting bonuses
 */
class CreditVoteService
{
    // Voting settings
    public const MIN_VOTE_VALUE = 0.1;
    public const MAX_VOTE_VALUE = 10.0;
    public const VOTING_BONUS = 1; // Credits awarded for voting
    
    // Vote change cooldown (days)
    public const VOTE_COOLDOWN_DAYS = 7;
    
    /**
     * Cast or update a vote on an item's credit rate
     */
    public function castVote(User $user, Item $item, float $voteValue, ?string $reason = null): array
    {
        // Validate vote value
        $voteValue = round(max(self::MIN_VOTE_VALUE, min(self::MAX_VOTE_VALUE, $voteValue)), 2);
        
        // Check for existing vote
        $existingVote = CreditVote::where('user_id', $user->id)
            ->where('item_id', $item->id)
            ->first();
        
        // Check cooldown for updates
        if ($existingVote && $existingVote->created_at->diffInDays(now()) < self::VOTE_COOLDOWN_DAYS) {
            return [
                'success' => false,
                'message' => 'You must wait ' . self::VOTE_COOLDOWN_DAYS . ' days before changing your vote',
            ];
        }
        
        // Get user's current balance for record
        $userBalance = $user->itcBalance?->balance ?? 0;
        
        // Get or create access value
        $accessValue = ItemAccessValue::firstOrNew(['item_id' => $item->id]);
        if (!$accessValue->exists) {
            $valuationService = app(AccessValuationService::class);
            $accessValue = $valuationService->setAccessValue($item);
        }
        
        // Create or update vote
        if ($existingVote) {
            $existingVote->vote_value = $voteValue;
            $existingVote->reason = $reason;
            $existingVote->user_balance_at_vote = $userBalance;
            $existingVote->save();
            $vote = $existingVote;
        } else {
            $vote = CreditVote::create([
                'user_id' => $user->id,
                'item_id' => $item->id,
                'vote_value' => $voteValue,
                'reason' => $reason,
                'user_balance_at_vote' => $userBalance,
            ]);
            
            // Award voting bonus for first vote
            $itcService = app(ItcService::class);
            $itcService->awardVotingBonus($user);
        }
        
        // Apply vote to access value
        $valuationService = app(AccessValuationService::class);
        $newRate = $valuationService->applyVote($accessValue, $voteValue, $userBalance);
        
        Log::info("Vote cast", [
            'user_id' => $user->id,
            'item_id' => $item->id,
            'vote_value' => $voteValue,
            'new_rate' => $newRate,
        ]);
        
        return [
            'success' => true,
            'message' => 'Vote recorded successfully',
            'vote' => $vote,
            'new_rate' => $newRate,
        ];
    }

    /**
     * Get all votes for an item
     */
    public function getItemVotes(Item $item): array
    {
        $votes = CreditVote::where('item_id', $item->id)
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return $votes->map(function ($vote) {
            return [
                'id' => $vote->id,
                'user' => [
                    'id' => $vote->user->id,
                    'name' => $vote->user->name,
                ],
                'vote_value' => $vote->vote_value,
                'reason' => $vote->reason,
                'created_at' => $vote->created_at->toIso8601String(),
            ];
        })->toArray();
    }

    /**
     * Get user's vote for an item
     */
    public function getUserVoteForItem(User $user, Item $item): ?CreditVote
    {
        return CreditVote::where('user_id', $user->id)
            ->where('item_id', $item->id)
            ->first();
    }

    /**
     * Get items user has voted on
     */
    public function getUserVotedItems(User $user): array
    {
        $votes = CreditVote::where('user_id', $user->id)
            ->with('item')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return $votes->map(function ($vote) {
            return [
                'item_id' => $vote->item_id,
                'item_name' => $vote->item->name ?? 'Unknown',
                'vote_value' => $vote->vote_value,
                'vote_date' => $vote->created_at->toIso8601String(),
            ];
        })->toArray();
    }

    /**
     * Get vote statistics for an item
     */
    public function getItemVoteStats(Item $item): array
    {
        $accessValue = ItemAccessValue::where('item_id', $item->id)->first();
        
        if (!$accessValue) {
            return [
                'vote_count' => 0,
                'average_vote' => 0,
                'current_rate' => ItemAccessValue::DEFAULT_DAILY_RATE,
            ];
        }
        
        return [
            'vote_count' => $accessValue->vote_count,
            'average_vote' => $accessValue->average_vote,
            'vote_total' => $accessValue->vote_total,
            'current_rate' => $accessValue->current_daily_rate,
            'current_weekly_rate' => $accessValue->current_weekly_rate,
            'base_rate' => $accessValue->base_credit_value,
            'last_change' => $accessValue->last_rate_change?->toIso8601String(),
        ];
    }

    /**
     * Check if user can vote on an item
     */
    public function canVote(User $user, Item $item): array
    {
        $existingVote = $this->getUserVoteForItem($user, $item);
        
        if ($existingVote) {
            $daysSinceVote = $existingVote->created_at->diffInDays(now());
            $cooldownRemaining = max(0, self::VOTE_COOLDOWN_DAYS - $daysSinceVote);
            
            if ($cooldownRemaining > 0) {
                return [
                    'can_vote' => false,
                    'reason' => 'cooldown',
                    'cooldown_days' => $cooldownRemaining,
                ];
            }
        }
        
        return [
            'can_vote' => true,
            'reason' => null,
        ];
    }
}

