import { Injectable, computed, inject, signal } from '@angular/core';

import { Release } from '../domain/release';
import { ReleasesApi } from '../infrastructure/releases-api';

@Injectable({ providedIn: 'root' })
export class ReleasesFacade {
  private readonly api = inject(ReleasesApi);

  private readonly _items = signal<ReadonlyArray<Release>>([]);
  private readonly _loading = signal(false);
  private readonly _error = signal<string | null>(null);

  private readonly _search = signal('');
  private readonly _activePlatforms = signal<ReadonlySet<string>>(new Set());

  readonly items = this._items.asReadonly();
  readonly loading = this._loading.asReadonly();
  readonly error = this._error.asReadonly();
  readonly search = this._search.asReadonly();
  readonly activePlatforms = this._activePlatforms.asReadonly();

  /** Plataformas presentes nos itens carregados (normalizadas via canonical map). */
  readonly availablePlatforms = computed<ReadonlyArray<string>>(() => {
    const seen = new Set<string>();
    for (const release of this._items()) {
      for (const platform of release.platforms) {
        seen.add(canonicalPlatform(platform));
      }
    }
    return [...seen].sort();
  });

  readonly visibleItems = computed(() => {
    const term = this._search().trim().toLowerCase();
    const platforms = this._activePlatforms();

    return this._items().filter((release) => {
      if (term !== '' && !release.name.toLowerCase().includes(term)) {
        return false;
      }
      if (platforms.size > 0) {
        const has = release.platforms.some((p) => platforms.has(canonicalPlatform(p)));
        if (!has) return false;
      }
      return true;
    });
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

    this.api.list(200, 90).subscribe({
      next: (items) => {
        this._items.set(items);
        this._loading.set(false);
      },
      error: (err: unknown) => {
        this._error.set(err instanceof Error ? err.message : 'Falha ao carregar lançamentos');
        this._loading.set(false);
      }
    });
  }

  setSearch(term: string): void {
    this._search.set(term);
  }

  togglePlatform(platform: string): void {
    const current = new Set(this._activePlatforms());
    if (current.has(platform)) {
      current.delete(platform);
    } else {
      current.add(platform);
    }
    this._activePlatforms.set(current);
  }

  resetFilters(): void {
    this._search.set('');
    this._activePlatforms.set(new Set());
  }
}

function canonicalPlatform(name: string): string {
  if (name === 'PC (Microsoft Windows)') return 'PC';
  if (name === 'PlayStation 5') return 'PS5';
  if (name === 'Xbox Series X|S') return 'Xbox';
  if (name === 'Nintendo Switch') return 'Switch';
  if (name === 'Nintendo Switch 2') return 'Switch 2';
  return name;
}
