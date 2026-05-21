import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';

import { environment } from '../../../../environments/environment';
import { NewsCluster } from '../domain/news-item';

interface NewsListResponse {
  readonly data: ReadonlyArray<NewsCluster>;
}

@Injectable({ providedIn: 'root' })
export class NewsApi {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = environment.apiBaseUrl;

  list(limit = 50): Observable<ReadonlyArray<NewsCluster>> {
    return this.http
      .get<NewsListResponse>(`${this.baseUrl}/news`, { params: { limit } })
      .pipe(map((response) => response.data));
  }
}
