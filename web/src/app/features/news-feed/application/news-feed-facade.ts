import { Injectable, computed, inject, signal } from '@angular/core';

import { NewsCluster } from '../domain/news-item';
import { NewsApi } from '../infrastructure/news-api';

@Injectable({ providedIn: 'root' })
export class NewsFeedFacade {
  private readonly api = inject(NewsApi);

  private readonly _items = signal<ReadonlyArray<NewsCluster>>([]);
  private readonly _loading = signal(false);
  private readonly _error = signal<string | null>(null);

  readonly items = this._items.asReadonly();
  readonly loading = this._loading.asReadonly();
  readonly error = this._error.asReadonly();
  readonly isEmpty = computed(() => !this._loading() && this._items().length === 0);

  load(): void {
    this._loading.set(true);
    this._error.set(null);

    this.api.list().subscribe({
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
}
