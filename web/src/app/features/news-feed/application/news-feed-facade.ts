import { Injectable, computed, inject, signal } from '@angular/core';

import { SparklinePoint } from '../../../shared/sparkline';
import { NewsCluster } from '../domain/news-item';
import { NewsApi } from '../infrastructure/news-api';
import { SnapshotsApi } from '../infrastructure/snapshots-api';

export type TimeWindow = '24h' | '72h' | '7d';

const WINDOW_HOURS: Record<TimeWindow, number> = {
  '24h': 24,
  '72h': 72,
  '7d': 168
};

@Injectable({ providedIn: 'root' })
export class NewsFeedFacade {
  private readonly api = inject(NewsApi);
  private readonly snapshotsApi = inject(SnapshotsApi);

  private readonly _items = signal<ReadonlyArray<NewsCluster>>([]);
  private readonly _loading = signal(false);
  private readonly _error = signal<string | null>(null);

  private readonly _activeSources = signal<ReadonlySet<string>>(new Set());
  private readonly _minThermometer = signal(0);
  private readonly _window = signal<TimeWindow>('72h');

  private readonly _snapshots = signal<Record<number, ReadonlyArray<SparklinePoint>>>({});

  readonly items = this._items.asReadonly();
  readonly loading = this._loading.asReadonly();
  readonly error = this._error.asReadonly();
  readonly activeSources = this._activeSources.asReadonly();
  readonly minThermometer = this._minThermometer.asReadonly();
  readonly window = this._window.asReadonly();

  /** Slugs presentes nos clusters carregados, em ordem alfabética. */
  readonly availableSources = computed<ReadonlyArray<{ slug: string; name: string }>>(() => {
    const seen = new Map<string, string>();
    for (const cluster of this._items()) {
      for (const source of cluster.sources) {
        if (!seen.has(source.slug)) {
          seen.set(source.slug, source.name);
        }
      }
    }
    return [...seen.entries()]
      .map(([slug, name]) => ({ slug, name }))
      .sort((a, b) => a.name.localeCompare(b.name));
  });

  readonly visibleItems = computed(() => {
    const activeSources = this._activeSources();
    const minScore = this._minThermometer();
    const windowMs = WINDOW_HOURS[this._window()] * 3_600_000;
    const cutoff = Date.now() - windowMs;

    return this._items().filter((cluster) => {
      if (cluster.thermometer < minScore) return false;
      if (Date.parse(cluster.publishedAt) < cutoff) return false;
      if (activeSources.size > 0) {
        const hit = cluster.sources.some((s) => activeSources.has(s.slug));
        if (!hit) return false;
      }
      return true;
    });
  });

  readonly isEmpty = computed(
    () => !this._loading() && this._items().length === 0
  );

  readonly isFilteredEmpty = computed(
    () =>
      !this._loading() &&
      this._items().length > 0 &&
      this.visibleItems().length === 0
  );

  readonly snapshotsFor = (clusterId: number): ReadonlyArray<SparklinePoint> =>
    this._snapshots()[clusterId] ?? [];

  load(): void {
    this._loading.set(true);
    this._error.set(null);

    this.api.list(200).subscribe({
      next: (items) => {
        this._items.set(items);
        this._loading.set(false);
        this.loadSnapshots(items.map((c) => c.id));
      },
      error: (err: unknown) => {
        this._error.set(err instanceof Error ? err.message : 'Falha ao carregar notícias');
        this._loading.set(false);
      }
    });
  }

  toggleSource(slug: string): void {
    const current = new Set(this._activeSources());
    if (current.has(slug)) {
      current.delete(slug);
    } else {
      current.add(slug);
    }
    this._activeSources.set(current);
  }

  setMinThermometer(value: number): void {
    this._minThermometer.set(Math.max(0, Math.min(100, value)));
  }

  setWindow(window: TimeWindow): void {
    this._window.set(window);
  }

  resetFilters(): void {
    this._activeSources.set(new Set());
    this._minThermometer.set(0);
    this._window.set('72h');
  }

  private loadSnapshots(clusterIds: ReadonlyArray<number>): void {
    const top = clusterIds.slice(0, 30);
    for (const id of top) {
      this.snapshotsApi.forCluster(id, 24).subscribe({
        next: (points) => {
          this._snapshots.update((map) => ({ ...map, [id]: points }));
        },
        error: () => {
          /* silencioso — sparkline some pra esse card */
        }
      });
    }
  }
}
