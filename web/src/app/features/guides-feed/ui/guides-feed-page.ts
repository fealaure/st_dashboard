import { DatePipe, NgClass } from '@angular/common';
import { Component, OnInit, inject } from '@angular/core';

import { CardSkeletonComponent } from '../../../shared/card-skeleton';
import { GuidesFeedFacade } from '../application/guides-feed-facade';

@Component({
  selector: 'ss-guides-feed-page',
  imports: [DatePipe, NgClass, CardSkeletonComponent],
  templateUrl: './guides-feed-page.html',
  styleUrl: './guides-feed-page.scss'
})
export class GuidesFeedPage implements OnInit {
  protected readonly facade = inject(GuidesFeedFacade);
  protected readonly skeletonCount = Array.from({ length: 6 });

  ngOnInit(): void {
    this.facade.load();
  }

  protected readonly chipClass = (slug: string, isActive: boolean): Record<string, boolean> => ({
    'guides-filters__chip': true,
    [`guides-filters__chip--${slug}`]: true,
    'is-active': isActive
  });
}
