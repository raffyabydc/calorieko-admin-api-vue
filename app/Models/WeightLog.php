<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Historical weight log entry.
 *
 * Each record represents a point-in-time weight measurement
 * for a specific user, enabling longitudinal trend analysis.
 */
class WeightLog extends Model
{
    protected $table = 'weight_logs';

    protected $fillable = [
        'uid',
        'weight',
        'recorded_at',
    ];

    protected $casts = [
        'weight'      => 'double',
        'recorded_at' => 'integer',
    ];

    /**
     * Relationship: The user profile this weight log belongs to.
     */
    public function userProfile()
    {
        return $this->belongsTo(UserProfile::class, 'uid', 'uid');
    }
}
