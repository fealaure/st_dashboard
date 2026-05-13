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
    path: 'releases',
    loadComponent: () =>
      import('./features/upcoming-releases/ui/upcoming-releases-page').then(
        (m) => m.UpcomingReleasesPage
      )
  }
];
