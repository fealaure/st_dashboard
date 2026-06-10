export interface NewsSource {
  readonly slug: string;
  readonly name: string;
}

/**
 * Um cluster representa uma "notícia" no dashboard — pode estar coberta
 * por 1 ou mais fontes. As matérias do mesmo assunto são agrupadas por
 * simhash no backend; o feed é cronológico.
 */
export interface NewsCluster {
  readonly id: number;
  readonly title: string;
  readonly url: string;
  readonly sources: ReadonlyArray<NewsSource>;
  readonly publishedAt: string;
  readonly firstSeenAt: string;
  readonly lastSeenAt: string;
}
