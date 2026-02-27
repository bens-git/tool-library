<?php

namespace App\Services;

use App\Models\Archetype;
use App\Models\ArchetypeAccessValue;
use App\Models\CreditVote;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Credit Vote Service - handles community voting on credit rates
 * 
 * This service manages:
 * - Recording user votes on archetype credit rates
 * - Calculating weighted votes
 * - Applying vote results to archetype access values
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
     * Cast or update a vote on an archetype's credit rate
     */
    public function castArchetypeVote(User $user, Archetype $archetype, float $voteValue, ?string $reason = null): array
    {
        // Validate vote value
        $voteValue = round(max(self::MIN_VOTE_VALUE, min(self::MAX_VOTE_VALUE, $voteValue)), 2);
        
        // Check for existing vote
        $existingVote = CreditVote::where('user_id', $user->id)
            ->where('archetype_id', $archetype->id)
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
        $accessValue = ArchetypeAccessValue::firstOrNew(['archetype_id' => $archetype->id]);
        if (!$accessValue->exists) {
            $accessValue->base_credit_value = ArchetypeAccessValue::DEFAULT_DAILY_RATE;
            $accessValue->current_daily_rate = ArchetypeAccessValue::DEFAULT_DAILY_RATE;
            $accessValue->current_weekly_rate = ArchetypeAccessValue::DEFAULT_WEEKLY_RATE;
            $accessValue->save();
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
                'archetype_id' => $archetype->id,
                'vote_value' => $voteValue,
                'reason' => $reason,
                'user_balance_at_vote' => $userBalance,
            ]);
            
            // Award voting bonus for first vote
            $itcService = app(ItcService::class);
            $itcService->awardVotingBonus($user);
        }
        
        // Apply vote to access value
        $newRate = $this->applyArchetypeVote($accessValue, $voteValue, $userBalance);
        
        Log::info("Archetype vote cast", [
            'user_id' => $user->id,
            'archetype_id' => $archetype->id,
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
     * Apply a vote to an archetype's access value
     */
    protected function applyArchetypeVote(ArchetypeAccessValue $accessValue, float $voteValue, float $userBalance): float
    {
        $accessValue->addVote($voteValue);
        return $accessValue->current_daily_rate;
    }

    /**
     * Get all votes for an archetype
     */
    public function getArchetypeVotes(Archetype $archetype): array
    {
        $votes = CreditVote::where('archetype_id', $archetype->id)
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
     * Get user's vote for an archetype
     */
    public function getUserVoteForArchetype(User $user, Archetype $archetype): ?CreditVote
    {
        return CreditVote::where('user_id', $user->id)
            ->where('archetype_id', $archetype->id)
            ->first();
    }

    /**
     * Get archetypes user has voted on
     */
    public function getUserVotedArchetypes(User $user): array
    {
        $votes = CreditVote::where('user_id', $user->id)
            ->whereNotNull('archetype_id')
            ->with('archetype')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return $votes->map(function ($vote) {
            return [
                'archetype_id' => $vote->archetype_id,
                'archetype_name' => $vote->archetype?->name ?? 'Unknown',
                'vote_value' => $vote->vote_value,
                'vote_date' => $vote->created_at->toIso8601String(),
            ];
        })->toArray();
    }

    /**
     * Get vote statistics for an archetype
     */
    public function getArchetypeVoteStats(Archetype $archetype): array
    {
        $accessValue = ArchetypeAccessValue::where('archetype_id', $archetype->id)->first();
        
        if (!$accessValue) {
            return [
                'vote_count' => 0,
                'average_vote' => 0,
                'current_rate' => ArchetypeAccessValue::DEFAULT_DAILY_RATE,
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
     * Check if user can vote on an archetype
     */
    public function canVoteOnArchetype(User $user, Archetype $archetype): array
    {
        $existingVote = $this->getUserVoteForArchetype($user, $archetype);
        
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

