<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use SaveState\Releases\Application\ListReleasesUseCase;

class ReleasesController extends Controller
{
    public function index(Request $request, ListReleasesUseCase $useCase): JsonResponse
    {
        $limit = (int) $request->query('limit', '100');
        $daysAhead = (int) $request->query('days', '90');

        $releases = $useCase->execute($limit, $daysAhead);

        $payload = array_map(static function ($release): array {
            return [
                'id' => $release->id,
                'igdbId' => $release->igdbId,
                'name' => $release->name,
                'slug' => $release->slug,
                'summary' => $release->summary,
                'coverUrl' => $release->coverUrl,
                'hype' => $release->hype,
                'releaseDate' => $release->releaseDate?->format('Y-m-d'),
                'platforms' => $release->platforms,
                'publishers' => $release->publishers,
                'igdbUrl' => $release->igdbUrl,
            ];
        }, $releases);

        return response()->json(['data' => $payload]);
    }
}
