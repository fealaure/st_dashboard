<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuideItemModel extends Model
{
    use HasFactory;

    protected $table = 'guide_items';

    protected $fillable = [
        'source_id',
        'external_id',
        'title',
        'url',
        'excerpt',
        'author',
        'published_at',
        'fetched_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'fetched_at' => 'datetime',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(GuideSourceModel::class, 'source_id');
    }
}
