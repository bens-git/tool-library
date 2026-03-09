<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Archetype extends Model
{
    use HasFactory;

    protected $fillable = ['id','name', 'description', 'created_by', 'notes', 'code', 'resource'];

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Get the access value for this archetype
     * This determines the flat rate credit cost for any item of this archetype
     */
    public function accessValue(): HasOne
    {
        return $this->hasOne(ArchetypeAccessValue::class);
    }

    /**
     * Get the current flat rate credit value for this archetype
     * Returns the flat fee (not daily rate) - the single charge per usage
     */
    public function getCurrentCreditValue(): float
    {
        if ($this->accessValue) {
            // Use current_daily_rate as the flat rate (single value per archetype)
            return $this->accessValue->current_daily_rate ?? ArchetypeAccessValue::DEFAULT_DAILY_RATE;
        }
        
        return ArchetypeAccessValue::DEFAULT_DAILY_RATE;
    }
}
