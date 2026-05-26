import { Injectable, computed, inject, signal } from '@angular/core';

import { Guide } from '../domain/guide';
import { GuidesApi } from '../infrastructure/guides-api';

@Injectable({ providedIn: 'root' })
export class GuidesFeedFacade {
  private readonly api = inject(GuidesApi);

  private readonly _items = signal<ReadonlyArray<Guide>>([]);
  private readonly _loading = signal(false);
  private readonly _error = signal<string | null>(null);
  private readonly _activeSources = signal<ReadonlySet<string>>(new Set());

  readonly items = this._items.asReadonly();
  readonly loading = this._loading.asReadonly();
  readonly error = this._error.asReadonly();
  readonly activeSources = this._activeSources.asReadonly();

  readonly availableSources = computed<ReadonlyArray<{ slug: string; name: string }>>(() => {
    const seen = new Map<string, string>();
    for (const guide of this._items()) {
      if (!seen.has(guide.source.slug)) {
        seen.set(guide.source.slug, guide.source.name);
      }
    }
    return [...seen.entries()]
      .map(([slug, name]) => ({ slug, name }))
      .sort((a, b) => a.name.localeCompare(b.name));
  });

  readonly visibleItems = computed(() => {
    const active = this._activeSources();
    if (active.size === 0) {
      return this._items();
    }
    return this._items().filter((g) => active.has(g.source.slug));
  });

  readonly isEmpty = computed(() => !this._loading() && this._items().length === 0);

  readonly isFilteredEmpty = computed(
    () =>
      !this._loading() &&
      this._items().length > 0 &&
      this.visibleItems().length === 0
  );

  load(): void {
    this._loading.set(true);
    this._error.set(null);

    this.api.list(200, 720).subscribe({
      next: (items) => {
        this._items.set(items);
        this._loading.set(false);
      },
      error: (err: unknown) => {
        this._error.set(err instanceof Error ? err.message : 'Falha ao carregar guias');
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

  resetFilters(): void {
    this._activeSources.set(new Set());
  }
}
