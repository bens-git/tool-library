<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'location_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the location associated with the user.
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Get the user's ITC balance
     */
    public function itcBalance()
    {
        return $this->hasOne(ItcBalance::class);
    }

    /**
     * Get all ITC ledger transactions
     */
    public function itcLedgers()
    {
        return $this->hasMany(ItcLedger::class);
    }

    /**
     * Get credit votes made by this user
     */
    public function creditVotes()
    {
        return $this->hasMany(CreditVote::class);
    }

    /**
     * Get current ITC balance value
     */
    public function getItcBalanceValue(): float
    {
        return $this->itcBalance?->balance ?? 0;
    }
}
