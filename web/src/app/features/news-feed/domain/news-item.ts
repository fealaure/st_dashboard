export interface NewsSource {
  readonly slug: string;
  readonly name: string;
}

export interface RedditAggregate {
  readonly upvotes: number;
  readonly comments: number;
  readonly syncedAt: string | null;
}

/**
 * Um cluster representa uma "notícia" no dashboard — pode estar coberta
 * por 1 ou mais fontes. O termômetro vive aqui, não no item individual.
 */
export interface NewsCluster {
  readonly id: number;
  readonly title: string;
  readonly url: string;
  readonly thermometer: number;
  readonly coverage: number;
  readonly sources: ReadonlyArray<NewsSource>;
  readonly reddit: RedditAggregate;
  readonly publishedAt: string;
  readonly firstSeenAt: string;
  readonly lastSeenAt: string;
}
