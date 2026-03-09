<?php

namespace App\Services;

use App\Models\ItcBalance;
use App\Models\ItcLedger;
use App\Models\Usage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ITC Service - handles all credit operations
 * 
 * This service manages:
 * - Credit balance operations (credit/debit)
 * - Usage transactions (lending/borrowing)
 * - Decay operations
 * - Integration points for future Integral system
 */
class ItcService
{
    // Configuration
    public const INITIAL_BONUS_CREDITS = 50; // New users get 50 credits
    public const VOTING_BONUS_CREDITS = 1;   // Bonus for participating in voting
    public const LENDING_BONUS_MULTIPLIER = 1.0; // Offerer earns 100% of usage rate
    public const MAX_DECAY_PERCENTAGE = 0.5; // Max 50% decay on balances

    /**
     * Get or create ITC balance for a user
     */
    public function getOrCreateBalance(User $user): ItcBalance
    {
        $balance = ItcBalance::firstOrNew(['user_id' => $user->id]);

        if (!$balance->exists) {
            $balance->balance = self::INITIAL_BONUS_CREDITS;
            $balance->lifetime_earned = self::INITIAL_BONUS_CREDITS;
            $balance->lifetime_spent = 0;
            $balance->last_decay_at = now();
            $balance->save();

            // Create initial ledger entry
            $this->createLedgerEntry(
                $user,
                self::INITIAL_BONUS_CREDITS,
                ItcLedger::TYPE_BONUS,
                ItcLedger::CATEGORY_ADMIN,
                'Welcome bonus - initial credits',
                null,
                null
            );
        }

        return $balance;
    }

    /**
     * Charge credits for borrowing an item (usage)
     */
    public function chargeForUsage(User $user, Usage $usage, float $credits): bool
    {
        $balance = $this->getOrCreateBalance($user);

        if (!$balance->hasEnough($credits)) {
            Log::warning("Insufficient credits for usage", [
                'user_id' => $user->id,
                'required' => $credits,
                'available' => $balance->balance
            ]);
            return false;
        }

        return DB::transaction(function () use ($user, $usage, $credits, $balance) {
            // Deduct credits
            $balance->balance -= $credits;
            $balance->lifetime_spent += $credits;
            $balance->save();

            // Create ledger entry
            $this->createLedgerEntry(
                $user,
                -$credits,
                ItcLedger::TYPE_SPENT,
                ItcLedger::CATEGORY_BORROWING,
                "Usage charge for item #{$usage->item_id}",
                $usage->item_id,
                $usage->id
            );

            // Update usage record
            $usage->credits_charged = $credits;
            $usage->save();

            return true;
        });
    }

    /**
     * Credit the offerer for lending an item
     */
    public function creditForLending(User $offerer, Usage $usage, float $credits): bool
    {
        $balance = $this->getOrCreateBalance($offerer);
        $earnedCredits = $credits * self::LENDING_BONUS_MULTIPLIER;

        return DB::transaction(function () use ($offerer, $usage, $earnedCredits, $balance) {
            // Add credits
            $balance->balance += $earnedCredits;
            $balance->lifetime_earned += $earnedCredits;
            $balance->save();

            // Create ledger entry
            $this->createLedgerEntry(
                $offerer,
                $earnedCredits,
                ItcLedger::TYPE_EARNED,
                ItcLedger::CATEGORY_LENDING,
                "Earnings from lending item #{$usage->item_id}",
                $usage->item_id,
                $usage->id
            );

            return true;
        });
    }

    /**
     * Process usage completion - charges user, credits offerer
     * Uses archetype-based flat rate (not duration-based)
     */
    public function processUsageCompletion(Usage $usage): bool
    {
        $item = $usage->item;

        if (!$item) {
            Log::error("Usage has no item", ['usage_id' => $usage->id]);
            return false;
        }

        // Get flat rate from archetype (single value per archetype, not duration-based)
        $flatRate = $item->getArchetypeCreditValue();

        // Total credits is just the flat rate - no duration calculation
        $totalCredits = $flatRate;

        // Get user and offerer
        $user = User::find($usage->used_by);
        $offerer = User::find($item->owned_by);

        if (!$user || !$offerer) {
            Log::error("Missing user for usage completion", [
                'usage_id' => $usage->id,
                'user_id' => $usage->used_by,
                'lender_id' => $item->owned_by
            ]);
            return false;
        }

        // Charge user
        $charged = $this->chargeForUsage($user, $usage, $totalCredits);

        if ($charged) {
            // Credit offerer
            $this->creditForLending($offerer, $usage, $totalCredits);

            // Mark usage as paid
            $usage->credits_paid = $totalCredits;
            $usage->credits_paid_at = now();
            $usage->save();
        }

        return $charged;
    }

    
    /**
     * Apply decay to all user balances
     * This reduces balances over time to prevent accumulation
     */
    public function applyDecay(): void
    {
        $balances = ItcBalance::where('balance', '>', 0)->get();

        foreach ($balances as $balance) {
            // Skip if recently decayed
            if ($balance->last_decay_at && $balance->last_decay_at->diffInDays(now()) < 30) {
                continue;
            }

            $decayAmount = $balance->balance * self::MAX_DECAY_PERCENTAGE;

            if ($decayAmount > 0) {
                $balance->balance -= $decayAmount;
                $balance->save();

                // Create ledger entry
                $this->createLedgerEntry(
                    $balance->user,
                    -$decayAmount,
                    ItcLedger::TYPE_DECAY,
                    ItcLedger::CATEGORY_ADMIN,
                    'Monthly balance decay',
                    null,
                    null
                );
            }
        }
    }

    /**
     * Award voting bonus to user
     */
    public function awardVotingBonus(User $user): void
    {
        $balance = $this->getOrCreateBalance($user);

        DB::transaction(function () use ($user, $balance) {
            $balance->balance += self::VOTING_BONUS_CREDITS;
            $balance->lifetime_earned += self::VOTING_BONUS_CREDITS;
            $balance->save();

            $this->createLedgerEntry(
                $user,
                self::VOTING_BONUS_CREDITS,
                ItcLedger::TYPE_BONUS,
                ItcLedger::CATEGORY_VOTING_BONUS,
                'Bonus for participating in credit rate voting',
                null,
                null
            );
        });
    }

    /**
     * Create a ledger entry
     */
    protected function createLedgerEntry(
        User $user,
        float $amount,
        string $type,
        string $category,
        ?string $description,
        ?int $itemId = null,
        ?int $usageId = null
    ): ItcLedger {
        $balance = $this->getOrCreateBalance($user);

        return ItcLedger::create([
            'user_id' => $user->id,
            'item_id' => $itemId,
            'usage_id' => $usageId,
            'type' => $type,
            'category' => $category,
            'amount' => $amount,
            'balance_after' => $balance->balance,
            'description' => $description,
        ]);
    }

    /**
     * Get transaction history for a user
     */
    public function getTransactionHistory(User $user, int $limit = 50)
    {
        return ItcLedger::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get user's current balance
     */
    public function getBalance(User $user): float
    {
        $balance = ItcBalance::where('user_id', $user->id)->first();
        return $balance->balance ?? 0;
    }

    /**
     * Check if user has enough credits
     */
    public function hasEnoughCredits(User $user, float $amount): bool
    {
        $balance = ItcBalance::where('user_id', $user->id)->first();
        return $balance && $balance->balance >= $amount;
    }

    /**
     * Get summary stats for a user
     */
    public function getUserStats(User $user): array
    {
        $balance = ItcBalance::where('user_id', $user->id)->first();

        return [
            'balance' => $balance->balance ?? 0,
            'lifetime_earned' => $balance->lifetime_earned ?? 0,
            'lifetime_spent' => $balance->lifetime_spent ?? 0,
            'transaction_count' => ItcLedger::where('user_id', $user->id)->count(),
        ];
    }
}
