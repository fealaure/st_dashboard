<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use SaveState\News\Application\ListNewsUseCase;

class NewsController extends Controller
{
    public function index(Request $request, ListNewsUseCase $useCase): JsonResponse
    {
        $limit = (int) $request->query('limit', '50');
        $maxAgeHours = (int) $request->query('hours', '72');

        $entries = $useCase->execute($limit, $maxAgeHours);

        $payload = array_map(static function (array $entry): array {
            $cluster = $entry['cluster'];
            $sources = $entry['sources'];
            $publishedAt = $entry['latestPublishedAt'];

            return [
                'id' => $cluster->id,
                'title' => $cluster->canonicalTitle,
                'url' => $cluster->canonicalUrl,
                'thermometer' => $cluster->thermometer,
                'coverage' => count($sources),
                'sources' => $sources,
                'publishedAt' => $publishedAt->format(\DateTimeInterface::ATOM),
                'firstSeenAt' => $cluster->firstSeenAt->format(\DateTimeInterface::ATOM),
                'lastSeenAt' => $cluster->lastSeenAt->format(\DateTimeInterface::ATOM),
            ];
        }, $entries);

        return response()->json(['data' => $payload]);
    }
}
