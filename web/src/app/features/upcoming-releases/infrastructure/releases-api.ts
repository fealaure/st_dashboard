import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';

import { Release } from '../domain/release';

@Injectable({ providedIn: 'root' })
export class ReleasesApi {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = '/api/v1';

  list(): Observable<ReadonlyArray<Release>> {
    return this.http.get<ReadonlyArray<Release>>(`${this.baseUrl}/releases`);
  }
}
