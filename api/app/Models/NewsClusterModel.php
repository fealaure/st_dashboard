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
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'simhash' => 'integer',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(NewsItemModel::class, 'cluster_id');
    }
}
