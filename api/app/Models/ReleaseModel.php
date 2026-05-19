<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReleaseModel extends Model
{
    use HasFactory;

    protected $table = 'releases';

    protected $fillable = [
        'igdb_id',
        'name',
        'slug',
        'summary',
        'cover_url',
        'hype',
        'release_date',
        'platforms',
        'publishers',
        'igdb_url',
        'last_synced_at',
    ];

    protected $casts = [
        'igdb_id' => 'integer',
        'hype' => 'integer',
        'release_date' => 'date',
        'platforms' => 'array',
        'publishers' => 'array',
        'last_synced_at' => 'datetime',
    ];
}
