import { ChangeDetectionStrategy, Component, input } from '@angular/core';

@Component({
  selector: 'ss-card-skeleton',
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    @switch (variant()) {
      @case ('news') {
        <div class="skeleton skeleton--news">
          <div class="skeleton__row skeleton__row--narrow">
            <span class="skeleton__bar skeleton__bar--badge"></span>
            <span class="skeleton__bar skeleton__bar--time"></span>
          </div>
          <span class="skeleton__bar skeleton__bar--title"></span>
          <span class="skeleton__bar skeleton__bar--title skeleton__bar--title-short"></span>
          <div class="skeleton__meter"></div>
        </div>
      }
      @case ('release') {
        <div class="skeleton skeleton--release">
          <div class="skeleton__cover"></div>
          <div class="skeleton__body">
            <span class="skeleton__bar skeleton__bar--title"></span>
            <span class="skeleton__bar skeleton__bar--time"></span>
            <div class="skeleton__row">
              <span class="skeleton__bar skeleton__bar--chip"></span>
              <span class="skeleton__bar skeleton__bar--chip"></span>
            </div>
          </div>
        </div>
      }
    }
  `,
  styles: [
    `
      :host {
        display: block;
      }

      .skeleton {
        background: var(--ss-bg-surface);
        border: 1px solid var(--ss-border);
        border-radius: var(--ss-radius-md);
        overflow: hidden;
        position: relative;

        &--news {
          padding: 16px;
          display: flex;
          flex-direction: column;
          gap: 10px;
        }

        &--release {
          display: flex;
          flex-direction: column;
        }
      }

      .skeleton__row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
      }

      .skeleton__row--narrow {
        margin-bottom: 4px;
      }

      .skeleton__bar {
        display: block;
        height: 12px;
        border-radius: 6px;
        background: linear-gradient(
          90deg,
          var(--ss-bg-elevated) 0%,
          var(--ss-bg-hover) 50%,
          var(--ss-bg-elevated) 100%
        );
        background-size: 200% 100%;
        animation: shimmer 1.4s linear infinite;

        &--badge {
          width: 60px;
          height: 18px;
          border-radius: 9px;
        }
        &--time {
          width: 50px;
        }
        &--title {
          height: 16px;
          width: 90%;
        }
        &--title-short {
          width: 65%;
        }
        &--chip {
          width: 40px;
          height: 16px;
          border-radius: 8px;
        }
      }

      .skeleton__meter {
        height: 6px;
        border-radius: 3px;
        background: linear-gradient(
          90deg,
          var(--ss-bg-elevated) 0%,
          var(--ss-bg-hover) 50%,
          var(--ss-bg-elevated) 100%
        );
        background-size: 200% 100%;
        animation: shimmer 1.4s linear infinite;
        margin-top: 4px;
      }

      .skeleton__cover {
        aspect-ratio: 3 / 4;
        background: linear-gradient(
          90deg,
          var(--ss-bg-elevated) 0%,
          var(--ss-bg-hover) 50%,
          var(--ss-bg-elevated) 100%
        );
        background-size: 200% 100%;
        animation: shimmer 1.4s linear infinite;
      }

      .skeleton__body {
        padding: 12px;
        display: flex;
        flex-direction: column;
        gap: 6px;
      }

      @keyframes shimmer {
        0% {
          background-position: 200% 0;
        }
        100% {
          background-position: -200% 0;
        }
      }
    `
  ]
})
export class CardSkeletonComponent {
  readonly variant = input.required<'news' | 'release'>();
}
