export interface NewsSource {
  readonly slug: string;
  readonly name: string;
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
  readonly publishedAt: string;
  readonly firstSeenAt: string;
  readonly lastSeenAt: string;
}
