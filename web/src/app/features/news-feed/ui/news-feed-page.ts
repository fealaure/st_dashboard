import { DatePipe, NgClass } from '@angular/common';
import { Component, OnInit, inject } from '@angular/core';

import { CardSkeletonComponent } from '../../../shared/card-skeleton';
import { NewsFeedFacade, TimeWindow } from '../application/news-feed-facade';

@Component({
  selector: 'ss-news-feed-page',
  imports: [DatePipe, NgClass, CardSkeletonComponent],
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
}
