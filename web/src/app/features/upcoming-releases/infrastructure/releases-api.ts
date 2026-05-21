import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';

import { environment } from '../../../../environments/environment';
import { Release } from '../domain/release';

interface ReleasesListResponse {
  readonly data: ReadonlyArray<Release>;
}

@Injectable({ providedIn: 'root' })
export class ReleasesApi {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = environment.apiBaseUrl;

  list(limit = 100, daysAhead = 90): Observable<ReadonlyArray<Release>> {
    return this.http
      .get<ReleasesListResponse>(`${this.baseUrl}/releases`, {
        params: { limit, days: daysAhead }
      })
      .pipe(map((response) => response.data));
  }
}
