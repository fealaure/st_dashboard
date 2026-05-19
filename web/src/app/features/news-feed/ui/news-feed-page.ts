import { DatePipe, DecimalPipe, NgClass } from '@angular/common';
import { Component, OnInit, inject } from '@angular/core';

import { CardSkeletonComponent } from '../../../shared/card-skeleton';
import { SparklineComponent } from '../../../shared/sparkline';
import { NewsFeedFacade, TimeWindow } from '../application/news-feed-facade';
import { NewsCluster } from '../domain/news-item';

@Component({
  selector: 'ss-news-feed-page',
  imports: [DatePipe, DecimalPipe, NgClass, CardSkeletonComponent, SparklineComponent],
  templateUrl: './news-feed-page.html',
  styleUrl: './news-feed-page.scss'
})
export class NewsFeedPage implements OnInit {
  protected readonly facade = inject(NewsFeedFacade);
  protected readonly skeletonCount = Array.from({ length: 8 });
  protected readonly windowOptions: ReadonlyArray<{ value: TimeWindow; label: string }> = [
    { value: '24h', label: '24h' },
    { value: '72h', label: '72h' },
    { value: '7d', label: '7 dias' }
  ];

  ngOnInit(): void {
    this.facade.load();
  }

  protected readonly sourceClass = (slug: string, active: boolean): Record<string, boolean> => ({
    'news-card__source': true,
    [`news-card__source--${slug}`]: true,
    'is-inactive': !active
  });

  protected readonly chipClass = (slug: string, isActive: boolean): Record<string, boolean> => ({
    'news-filters__chip': true,
    [`news-filters__chip--${slug}`]: true,
    'is-active': isActive
  });

  protected redditTitle(cluster: NewsCluster): string {
    const total = cluster.reddit.upvotes + cluster.reddit.comments;
    if (total === 0) {
      return 'Sem sinal do Reddit ainda';
    }
    return `${cluster.reddit.upvotes} upvotes e ${cluster.reddit.comments} comentários no Reddit`;
  }

  protected onThermometerChange(event: Event): void {
    const value = Number((event.target as HTMLInputElement).value);
    this.facade.setMinThermometer(value);
  }
}
