export interface Release {
  readonly id: string;
  readonly name: string;
  readonly platforms: ReadonlyArray<string>;
  readonly publisher: string;
  readonly releaseDate: string;
  readonly coverUrl: string | null;
  readonly hype: number;
}
