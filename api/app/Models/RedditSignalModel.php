<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RedditSignalModel extends Model
{
    use HasFactory;

    protected $table = 'reddit_signals';

    protected $fillable = [
        'cluster_id',
        'reddit_post_id',
        'subreddit',
        'title',
        'permalink',
        'score',
        'num_comments',
        'posted_at',
        'captured_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'num_comments' => 'integer',
        'posted_at' => 'datetime',
        'captured_at' => 'datetime',
    ];

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(NewsClusterModel::class, 'cluster_id');
    }
}
