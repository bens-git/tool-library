<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ITC (Integral Time Credits) Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the Time Credit system
    | which can be integrated with the Integral system in the future.
    |
    */

    // Initial bonus credits for new users
    'initial_bonus' => env('ITC_INITIAL_BONUS', 50),

    // Base formula: credits per dollar of purchase value
    // $100 purchase value = 1 credit/day
    'base_value_multiplier' => env('ITC_BASE_VALUE_MULTIPLIER', 0.01),

    // Min/Max daily rates
    'min_daily_rate' => env('ITC_MIN_DAILY_RATE', 0.1),
    'max_daily_rate' => env('ITC_MAX_DAILY_RATE', 10.0),

    // Voting bonus credits
    'voting_bonus' => env('ITC_VOTING_BONUS', 1),

    // Vote cooldown (days)
    'vote_cooldown_days' => env('ITC_VOTE_COOLDOWN_DAYS', 7),

    // Lending bonus multiplier (what % of rental cost lender earns)
    'lending_bonus_multiplier' => env('ITC_LENDING_BONUS_MULTIPLIER', 1.0),

    // Decay settings
    'decay' => [
        // Max percentage of balance that can decay
        'max_percentage' => env('ITC_DECAY_MAX_PERCENTAGE', 0.5),
        
        // Days between decay operations
        'interval_days' => env('ITC_DECAY_INTERVAL_DAYS', 30),
        
        // Daily decay rate for item values
        'item_daily_rate' => env('ITC_DECAY_ITEM_DAILY_RATE', 0.0001),
    ],

    // Integral system integration settings
    'integral' => [
        // Enable integration with Integral system
        'enabled' => env('ITC_INTEGRAL_ENABLED', false),
        
        // API endpoint for Integral system
        'api_url' => env('ITC_INTEGRAL_API_URL', ''),
        
        // API key for authentication
        'api_key' => env('ITC_INTEGRAL_API_KEY', ''),
        
        // Sync mode: 'push' (Tool Library -> Integral) or 'pull' (Integral -> Tool Library)
        'sync_mode' => env('ITC_INTEGRAL_SYNC_MODE', 'push'),
    ],
];

