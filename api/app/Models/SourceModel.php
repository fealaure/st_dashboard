<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SourceModel extends Model
{
    use HasFactory;

    protected $table = 'sources';

    protected $fillable = [
        'slug',
        'name',
        'rss_url',
        'website_url',
        'weight',
        'active',
        'last_fetched_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'weight' => 'float',
        'last_fetched_at' => 'datetime',
    ];

    public function newsItems(): HasMany
    {
        return $this->hasMany(NewsItemModel::class, 'source_id');
    }
}
