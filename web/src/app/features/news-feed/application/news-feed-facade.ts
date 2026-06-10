import { Injectable, computed, inject, signal } from '@angular/core';

import { NewsCluster } from '../domain/news-item';
import { NewsApi } from '../infrastructure/news-api';

export type TimeWindow = '24h' | '72h' | '7d';

const WINDOW_HOURS: Record<TimeWindow, number> = {
  '24h': 24,
  '72h': 72,
  '7d': 168
};

@Injectable({ providedIn: 'root' })
export class NewsFeedFacade {
  private readonly api = inject(NewsApi);

  private readonly _items = signal<ReadonlyArray<NewsCluster>>([]);
  private readonly _loading = signal(false);
  private readonly _error = signal<string | null>(null);

  private readonly _activeSources = signal<ReadonlySet<string>>(new Set());
  private readonly _window = signal<TimeWindow>('72h');

  readonly items = this._items.asReadonly();
  readonly loading = this._loading.asReadonly();
  readonly error = this._error.asReadonly();
  readonly activeSources = this._activeSources.asReadonly();
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
    const windowMs = WINDOW_HOURS[this._window()] * 3_600_000;
    const cutoff = Date.now() - windowMs;

    return this._items().filter((cluster) => {
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

  load(): void {
    this._loading.set(true);
    this._error.set(null);

    this.api.list(200).subscribe({
      next: (items) => {
        this._items.set(items);
        this._loading.set(false);
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

  setWindow(window: TimeWindow): void {
    this._window.set(window);
  }

  resetFilters(): void {
    this._activeSources.set(new Set());
    this._window.set('72h');
  }
}
