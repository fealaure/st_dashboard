<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use SaveState\Guides\Application\ListGuidesUseCase;

class GuidesController extends Controller
{
    public function index(Request $request, ListGuidesUseCase $useCase): JsonResponse
    {
        $limit = (int) $request->query('limit', '50');
        $maxAgeHours = (int) $request->query('hours', '720');

        $entries = $useCase->execute($limit, $maxAgeHours);

        $payload = array_map(static function (array $entry): array {
            $guide = $entry['guide'];
            $source = $entry['source'];

            return [
                'id' => $guide->id,
                'title' => $guide->title,
                'url' => $guide->url,
                'excerpt' => $guide->excerpt,
                'author' => $guide->author,
                'source' => [
                    'slug' => $source->slug,
                    'name' => $source->name,
                ],
                'publishedAt' => $guide->publishedAt->format(\DateTimeInterface::ATOM),
            ];
        }, $entries);

        return response()->json(['data' => $payload]);
    }
}
