import { DatePipe, DecimalPipe, NgClass } from '@angular/common';
import { Component, OnInit, inject } from '@angular/core';

import { NewsFeedFacade } from '../application/news-feed-facade';

@Component({
  selector: 'ss-news-feed-page',
  imports: [DatePipe, DecimalPipe, NgClass],
  templateUrl: './news-feed-page.html',
  styleUrl: './news-feed-page.scss'
})
export class NewsFeedPage implements OnInit {
  protected readonly facade = inject(NewsFeedFacade);

  ngOnInit(): void {
    this.facade.load();
  }

  protected readonly sourceClass = (slug: string): Record<string, boolean> => ({
    'news-card__source': true,
    [`news-card__source--${slug}`]: true
  });
}
