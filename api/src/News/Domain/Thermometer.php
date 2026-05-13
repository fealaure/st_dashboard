<?php

declare(strict_types=1);

namespace SaveState\News\Domain;

use DateTimeImmutable;

/**
 * Calcula o score 0-100 de relevância de um cluster.
 *
 * Fase 2: score = 60 * cobertura + 40 * recência
 *   cobertura  = sources_distintas_no_cluster / total_sources_ativas (clamp 0..1)
 *   recência   = exp(-horas_desde_publicacao / tau), tau = 12h
 *
 * Quando o sinal do Reddit entrar (Fase 3), os pesos passam a 40/40/20.
 */
final class Thermometer
{
    public const COVERAGE_WEIGHT = 60.0;
    public const RECENCY_WEIGHT = 40.0;
    public const RECENCY_TAU_HOURS = 12.0;

    public static function compute(
        int $distinctSources,
        int $totalActiveSources,
        DateTimeImmutable $latestPublishedAt,
        DateTimeImmutable $now,
    ): float {
        if ($totalActiveSources <= 0) {
            return 0.0;
        }

        $coverage = min(1.0, $distinctSources / $totalActiveSources);

        $hoursAgo = max(0.0, ($now->getTimestamp() - $latestPublishedAt->getTimestamp()) / 3600.0);
        $recency = exp(-$hoursAgo / self::RECENCY_TAU_HOURS);

        $score = self::COVERAGE_WEIGHT * $coverage + self::RECENCY_WEIGHT * $recency;

        return round(max(0.0, min(100.0, $score)), 2);
    }
}
