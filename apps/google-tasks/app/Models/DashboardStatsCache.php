<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardStatsCache extends Model
{
    protected $table = 'dashboard_stats_cache';

    protected $fillable = ['user_id', 'stats', 'computed_at'];

    protected function casts(): array
    {
        return [
            'stats' => 'array',
            'computed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
