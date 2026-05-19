import { DatePipe, NgClass } from '@angular/common';
import { Component, OnInit, inject } from '@angular/core';

import { CardSkeletonComponent } from '../../../shared/card-skeleton';
import { ReleasesFacade } from '../application/releases-facade';

@Component({
  selector: 'ss-upcoming-releases-page',
  imports: [DatePipe, NgClass, CardSkeletonComponent],
  templateUrl: './upcoming-releases-page.html',
  styleUrl: './upcoming-releases-page.scss'
})
export class UpcomingReleasesPage implements OnInit {
  protected readonly facade = inject(ReleasesFacade);
  protected readonly skeletonCount = Array.from({ length: 10 });

  ngOnInit(): void {
    this.facade.load();
  }

  protected platformShort(platform: string): string {
    const map: Record<string, string> = {
      'PC (Microsoft Windows)': 'PC',
      'PlayStation 5': 'PS5',
      'Xbox Series X|S': 'Xbox',
      'Nintendo Switch': 'Switch',
      'Nintendo Switch 2': 'Switch 2'
    };
    return map[platform] ?? platform;
  }

  protected platformClass(platform: string): string {
    const short = this.platformShort(platform).toLowerCase().replace(/[^a-z0-9]/g, '-');
    return `release-card__platform release-card__platform--${short}`;
  }

  protected chipClass(platform: string): Record<string, boolean> {
    const slug = platform.toLowerCase().replace(/[^a-z0-9]/g, '-');
    return {
      'releases-filters__chip': true,
      [`releases-filters__chip--${slug}`]: true,
      'is-active': this.facade.activePlatforms().has(platform)
    };
  }

  protected onSearchInput(event: Event): void {
    this.facade.setSearch((event.target as HTMLInputElement).value);
  }
}
