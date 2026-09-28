<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsEvent extends Model
{
    protected $fillable = ['assignment_id', 'type'];

    public static function record(string $assignmentId, string $type): void
    {
        static::firstOrCreate(['assignment_id' => $assignmentId, 'type' => $type]);
    }
}
