export interface GuideSource {
  readonly slug: string;
  readonly name: string;
}

export interface Guide {
  readonly id: number;
  readonly title: string;
  readonly url: string;
  readonly excerpt: string | null;
  readonly author: string | null;
  readonly source: GuideSource;
  readonly publishedAt: string;
}
