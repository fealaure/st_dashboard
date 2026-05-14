<?php

declare(strict_types=1);

namespace SaveState\News\Domain;

use DateTimeImmutable;

/**
 * Calcula o score 0–100 de relevância de um cluster.
 *
 * score = 40 * cobertura + 40 * reddit + 20 * recência
 *
 *   cobertura = sources_distintas_no_cluster / total_sources_ativas (clamp 0..1)
 *   reddit    = log10(1 + upvotes + 2 * comments) / log10(REDDIT_SATURATION) (clamp 0..1)
 *   recência  = exp(-horas_desde_publicacao / tau), tau = 12h
 *
 * Quando não há sinal do Reddit (upvotes = comments = 0), o componente reddit fica
 * zerado e o cluster só sobe via cobertura + recência — o que é o desejado:
 * "viralizou no Reddit" é um sinal positivo real, não uma penalidade na ausência.
 */
final class Thermometer
{
    public const COVERAGE_WEIGHT = 40.0;
    public const REDDIT_WEIGHT = 40.0;
    public const RECENCY_WEIGHT = 20.0;
    public const RECENCY_TAU_HOURS = 12.0;

    /** Valor de upvotes+2·comments a partir do qual o componente reddit satura em 1.0. */
    public const REDDIT_SATURATION = 10000.0;

    public static function compute(
        int $distinctSources,
        int $totalActiveSources,
        int $redditUpvotes,
        int $redditComments,
        DateTimeImmutable $latestPublishedAt,
        DateTimeImmutable $now,
    ): ThermometerResult {
        $coverage = $totalActiveSources > 0
            ? min(1.0, $distinctSources / $totalActiveSources)
            : 0.0;

        $redditRaw = max(0, $redditUpvotes) + 2 * max(0, $redditComments);
        $reddit = $redditRaw > 0
            ? min(1.0, log10(1 + $redditRaw) / log10(self::REDDIT_SATURATION))
            : 0.0;

        $hoursAgo = max(0.0, ($now->getTimestamp() - $latestPublishedAt->getTimestamp()) / 3600.0);
        $recency = exp(-$hoursAgo / self::RECENCY_TAU_HOURS);

        $coverageComp = round(self::COVERAGE_WEIGHT * $coverage, 2);
        $redditComp = round(self::REDDIT_WEIGHT * $reddit, 2);
        $recencyComp = round(self::RECENCY_WEIGHT * $recency, 2);

        $score = round(max(0.0, min(100.0, $coverageComp + $redditComp + $recencyComp)), 2);

        return new ThermometerResult(
            score: $score,
            coverageComponent: $coverageComp,
            redditComponent: $redditComp,
            recencyComponent: $recencyComp,
        );
    }
}
