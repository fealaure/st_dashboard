<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsClusterModel extends Model
{
    use HasFactory;

    protected $table = 'news_clusters';

    protected $fillable = [
        'simhash',
        'canonical_title',
        'canonical_url',
        'thermometer',
        'reddit_upvotes',
        'reddit_comments',
        'reddit_synced_at',
        'thermometer_updated_at',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'simhash' => 'integer',
        'thermometer' => 'float',
        'reddit_upvotes' => 'integer',
        'reddit_comments' => 'integer',
        'reddit_synced_at' => 'datetime',
        'thermometer_updated_at' => 'datetime',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(NewsItemModel::class, 'cluster_id');
    }

    public function redditSignals(): HasMany
    {
        return $this->hasMany(RedditSignalModel::class, 'cluster_id');
    }
}
