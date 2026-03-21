<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskEmbedding extends Model
{
    protected $fillable = [
        'user_id',
        'task_list_id',
        'task_id',
        'content_hash',
        'dimensions',
        'embedding',
        'task_list_title',
        'title_raw',
        'notes_raw',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'embedding' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
