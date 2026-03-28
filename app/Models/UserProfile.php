<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    protected $table = 'user_profile';
    protected $primaryKey = 'uid';
    public $incrementing = false;        // uid is a string, not auto-increment
    protected $keyType = 'string';

    protected $fillable = [
        'uid',
        'name',
        'email',
        'age',
        'weight',
        'height',
        'sex',
        'activityLevel',
        'goal',
        'streak',
        'level',
        'is_active',
    ];

    protected $appends = ['is_engaged'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'name'      => 'encrypted',
            'email'     => 'encrypted',
            'age'       => 'integer',
            'weight'    => 'double',
            'height'    => 'double',
            'streak'    => 'integer',
            'level'     => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Virtual Attribute: Is the user behaviorally engaged?
     * Must be an active account AND actively tracking (streak > 0).
     */
    public function getIsEngagedAttribute(): bool
    {
        return $this->is_active && $this->streak > 0;
    }

    /**
     * Scope: Users who are legally allowed to log in (not suspended).
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Users who are BOTH active accounts AND behaviorally engaged.
     */
    public function scopeEngaged($query)
    {
        return $query->active()->where('streak', '>', 0);
    }
}
