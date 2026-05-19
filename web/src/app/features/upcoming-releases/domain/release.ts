export interface Release {
  readonly id: number;
  readonly igdbId: number;
  readonly name: string;
  readonly slug: string | null;
  readonly summary: string | null;
  readonly coverUrl: string | null;
  readonly hype: number;
  readonly releaseDate: string | null;
  readonly platforms: ReadonlyArray<string>;
  readonly publishers: ReadonlyArray<string>;
  readonly igdbUrl: string | null;
}
