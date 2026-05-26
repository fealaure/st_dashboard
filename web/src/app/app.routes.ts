import { Routes } from '@angular/router';

export const routes: Routes = [
  {
    path: '',
    pathMatch: 'full',
    redirectTo: 'news'
  },
  {
    path: 'news',
    loadComponent: () =>
      import('./features/news-feed/ui/news-feed-page').then((m) => m.NewsFeedPage)
  },
  {
    path: 'guides',
    loadComponent: () =>
      import('./features/guides-feed/ui/guides-feed-page').then((m) => m.GuidesFeedPage)
  },
  {
    path: 'releases',
    loadComponent: () =>
      import('./features/upcoming-releases/ui/upcoming-releases-page').then(
        (m) => m.UpcomingReleasesPage
      )
  }
];
