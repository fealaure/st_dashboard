<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThermometerSnapshotModel extends Model
{
    use HasFactory;

    protected $table = 'thermometer_snapshots';

    protected $fillable = [
        'cluster_id',
        'thermometer',
        'coverage_component',
        'reddit_component',
        'recency_component',
        'captured_at',
    ];

    protected $casts = [
        'thermometer' => 'float',
        'coverage_component' => 'float',
        'reddit_component' => 'float',
        'recency_component' => 'float',
        'captured_at' => 'datetime',
    ];

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(NewsClusterModel::class, 'cluster_id');
    }
}
