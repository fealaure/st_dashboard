import { Injectable, computed, inject, signal } from '@angular/core';

import { Release } from '../domain/release';
import { ReleasesApi } from '../infrastructure/releases-api';

@Injectable({ providedIn: 'root' })
export class ReleasesFacade {
  private readonly api = inject(ReleasesApi);

  private readonly _items = signal<ReadonlyArray<Release>>([]);
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
        this._error.set(err instanceof Error ? err.message : 'Falha ao carregar lançamentos');
        this._loading.set(false);
      }
    });
  }
}
