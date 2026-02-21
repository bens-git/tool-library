<?php

namespace Database\Seeders;

use App\Models\ItcBalance;
use App\Models\ItcLedger;
use App\Models\Item;
use App\Models\ItemAccessValue;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * ITC Seeder - Seeds initial ITC data
 * 
 * This seeder:
 * - Creates ITC balances for all users (50 initial credits)
 * - Creates item access values based on purchase prices
 * - Formula: 1 credit per $100 of purchase value per day
 */
class ItcSeeder extends Seeder
{
    /**
     * Initial bonus credits for new users
     */
    public const INITIAL_BONUS = 50;

    /**
     * Base value: credits per dollar of purchase value
     * $100 = 1 credit/day
     */
    public const BASE_CREDITS_PER_DOLLAR = 0.01;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Seeding ITC data...');

        // Create ITC balances for all users
        $this->seedUserBalances();

        // Create item access values based on purchase prices
        $this->seedItemAccessValues();

        $this->command->info('ITC seeding complete!');
    }

    /**
     * Create ITC balances for all users
     */
    protected function seedUserBalances(): void
    {
        $users = User::all();
        $created = 0;

        foreach ($users as $user) {
            // Check if balance already exists
            $existing = ItcBalance::where('user_id', $user->id)->first();
            
            if (!$existing) {
                DB::transaction(function () use ($user) {
                    // Create balance
                    $balance = ItcBalance::create([
                        'user_id' => $user->id,
                        'balance' => self::INITIAL_BONUS,
                        'lifetime_earned' => self::INITIAL_BONUS,
                        'lifetime_spent' => 0,
                        'last_decay_at' => now(),
                    ]);

                    // Create initial ledger entry
                    ItcLedger::create([
                        'user_id' => $user->id,
                        'item_id' => null,
                        'rental_id' => null,
                        'type' => 'bonus',
                        'category' => 'admin',
                        'amount' => self::INITIAL_BONUS,
                        'balance_after' => self::INITIAL_BONUS,
                        'description' => 'Welcome bonus - initial credits',
                    ]);
                });

                $created++;
            }
        }

        $this->command->info("Created {$created} ITC balances with " . self::INITIAL_BONUS . " initial credits each");
    }

    /**
     * Create item access values based on purchase prices
     */
    protected function seedItemAccessValues(): void
    {
        $items = Item::whereNotNull('purchase_value')
            ->where('purchase_value', '>', 0)
            ->get();
        
        $created = 0;

        foreach ($items as $item) {
            // Check if access value already exists
            $existing = ItemAccessValue::where('item_id', $item->id)->first();
            
            if (!$existing) {
                $baseValue = $this->calculateBaseValue($item->purchase_value);
                
                ItemAccessValue::create([
                    'item_id' => $item->id,
                    'purchase_value' => $item->purchase_value,
                    'base_credit_value' => $baseValue,
                    'current_daily_rate' => $baseValue,
                    'current_weekly_rate' => $baseValue * 5,
                    'vote_count' => 0,
                    'vote_total' => 0,
                    'average_vote' => 0,
                    'decay_rate' => 0.0001,
                    'last_rate_change' => now(),
                ]);

                $created++;
            }
        }

        // Also create default values for items with $0 or null purchase value
        $itemsWithNoValue = Item::where(function ($query) {
            $query->whereNull('purchase_value')
                ->orWhere('purchase_value', '<=', 0);
        })->get();

        foreach ($itemsWithNoValue as $item) {
            $existing = ItemAccessValue::where('item_id', $item->id)->first();
            
            if (!$existing) {
                ItemAccessValue::create([
                    'item_id' => $item->id,
                    'purchase_value' => 0,
                    'base_credit_value' => 1.00,
                    'current_daily_rate' => 1.00,
                    'current_weekly_rate' => 5.00,
                    'vote_count' => 0,
                    'vote_total' => 0,
                    'average_vote' => 0,
                    'decay_rate' => 0.0001,
                    'last_rate_change' => now(),
                ]);

                $created++;
            }
        }

        $this->command->info("Created {$created} item access values based on purchase prices");
    }

    /**
     * Calculate base credit value from purchase price
     * Formula: purchase_value * BASE_CREDITS_PER_DOLLAR
     */
    protected function calculateBaseValue(float $purchaseValue): float
    {
        if ($purchaseValue <= 0) {
            return 1.00; // Default minimum
        }

        $value = $purchaseValue * self::BASE_CREDITS_PER_DOLLAR;
        
        // Clamp between 0.1 and 10
        return round(max(0.1, min(10.0, $value)), 2);
    }
}

