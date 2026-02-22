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

    /**
     * Get conversations this user participates in.
     */
    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withTimestamps();
    }

    /**
     * Get messages sent by this user.
     */
    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get private conversations for this user.
     */
    public function privateConversations()
    {
        return $this->conversations()->where('type', 'private');
    }

    /**
     * Get unread message count for this user.
     */
    public function unreadMessageCount(): int
    {
        $conversations = $this->conversations()->with('messages')->get();
        $count = 0;
        
        foreach ($conversations as $conversation) {
            foreach ($conversation->messages as $message) {
                if ($message->user_id !== $this->id && !$message->isReadBy($this->id)) {
                    $count++;
                }
            }
        }
        
        return $count;
    }
}
