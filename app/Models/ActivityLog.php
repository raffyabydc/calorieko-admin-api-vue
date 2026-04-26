<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_log_table';
    protected $primaryKey = 'id';

    protected $fillable = [
        'uid',
        'type',
        'name',
        'timeString',
        'weightOrDuration',
        'calories',
        'protein',
        'carbs',
        'fats',
        'sodium',
        'timestamp',
        'distanceKm',
        'pace',
        'movingTimeSeconds',
        'steps',
        'mapType',
        'notes',
        'activityTag',
    ];

    protected $casts = [
        'calories'  => 'integer',
        'protein'   => 'integer',
        'carbs'     => 'integer',
        'fats'      => 'integer',
        'sodium'    => 'integer',
        'timestamp' => 'integer',
        'distanceKm'=> 'double',
        'pace'      => 'double',
        'movingTimeSeconds' => 'integer',
        'steps'     => 'integer',
    ];
}
