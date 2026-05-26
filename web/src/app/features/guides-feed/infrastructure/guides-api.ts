import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';

import { environment } from '../../../../environments/environment';
import { Guide } from '../domain/guide';

interface GuidesListResponse {
  readonly data: ReadonlyArray<Guide>;
}

@Injectable({ providedIn: 'root' })
export class GuidesApi {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = environment.apiBaseUrl;

  list(limit = 50, hours = 720): Observable<ReadonlyArray<Guide>> {
    return this.http
      .get<GuidesListResponse>(`${this.baseUrl}/guides`, { params: { limit, hours } })
      .pipe(map((response) => response.data));
  }
}
