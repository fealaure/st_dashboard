import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, map } from 'rxjs';

import { environment } from '../../../../environments/environment';
import { SparklinePoint } from '../../../shared/sparkline';

interface SnapshotsResponse {
  readonly data: ReadonlyArray<SparklinePoint>;
}

@Injectable({ providedIn: 'root' })
export class SnapshotsApi {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = environment.apiBaseUrl;

  forCluster(clusterId: number, hours = 24): Observable<ReadonlyArray<SparklinePoint>> {
    return this.http
      .get<SnapshotsResponse>(`${this.baseUrl}/news/${clusterId}/snapshots`, {
        params: { hours }
      })
      .pipe(map((response) => response.data));
  }
}
