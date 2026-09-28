<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    protected $fillable = ['assignment_id', 'email', 'checklist'];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime', 'checklist' => 'array'];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }
}
