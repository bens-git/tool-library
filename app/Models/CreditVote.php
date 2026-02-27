<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Credit Vote Model - community voting on archetype credit rates
 * 
 * @property int $id
 * @property int $user_id
 * @property int|null $archetype_id
 * @property float $vote_value
 * @property string|null $reason
 * @property float|null $user_balance_at_vote
 */
class CreditVote extends Model
{
    protected $fillable = [
        'user_id',
        'archetype_id',
        'vote_value',
        'reason',
        'user_balance_at_vote',
    ];

    protected $casts = [
        'vote_value' => 'decimal:2',
        'user_balance_at_vote' => 'decimal:2',
    ];

    // Min/Max vote values
    public const MIN_VOTE = 0.1;
    public const MAX_VOTE = 10.0;

    /**
     * Get the user who made this vote
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the archetype being voted on
     */
    public function archetype(): BelongsTo
    {
        return $this->belongsTo(Archetype::class);
    }

    /**
     * Validate vote value is within bounds
     */
    public static function isValidVote(float $value): bool
    {
        return $value >= self::MIN_VOTE && $value <= self::MAX_VOTE;
    }

    /**
     * Get formatted vote value
     */
    public function getFormattedVoteAttribute(): string
    {
        return number_format($this->vote_value, 2) . ' ITC/day';
    }
}

