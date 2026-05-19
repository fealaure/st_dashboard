import { ChangeDetectionStrategy, Component, computed, input } from '@angular/core';

export interface SparklinePoint {
  readonly capturedAt: string;
  readonly thermometer: number;
}

@Component({
  selector: 'ss-sparkline',
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    @if (path(); as p) {
      <svg
        class="sparkline"
        [attr.viewBox]="'0 0 ' + width + ' ' + height"
        preserveAspectRatio="none"
        aria-hidden="true"
      >
        <path class="sparkline__area" [attr.d]="p.area" />
        <path class="sparkline__line" [attr.d]="p.line" />
        <circle
          class="sparkline__dot"
          [attr.cx]="p.lastX"
          [attr.cy]="p.lastY"
          r="2"
        />
      </svg>
    } @else {
      <span class="sparkline__empty">sem dados ainda</span>
    }
  `,
  styles: [
    `
      :host {
        display: inline-flex;
        width: 100%;
        height: 24px;
      }

      .sparkline {
        width: 100%;
        height: 100%;
        overflow: visible;
      }

      .sparkline__line {
        fill: none;
        stroke: var(--ss-accent);
        stroke-width: 1.5;
        stroke-linecap: round;
        stroke-linejoin: round;
      }

      .sparkline__area {
        fill: var(--ss-accent);
        opacity: 0.18;
      }

      .sparkline__dot {
        fill: var(--ss-accent);
      }

      .sparkline__empty {
        font-size: 10px;
        color: var(--ss-text-subtle);
        font-style: italic;
      }
    `
  ]
})
export class SparklineComponent {
  protected readonly width = 100;
  protected readonly height = 24;

  readonly points = input.required<ReadonlyArray<SparklinePoint>>();

  protected readonly path = computed(() => {
    const data = this.points();
    if (data.length < 2) {
      return null;
    }

    const xs = data.map((_, i) => (i / (data.length - 1)) * this.width);
    const ys = data.map((p) => {
      const score = Math.max(0, Math.min(100, p.thermometer));
      return this.height - (score / 100) * (this.height - 2) - 1;
    });

    const line = xs.map((x, i) => `${i === 0 ? 'M' : 'L'}${x.toFixed(2)},${ys[i].toFixed(2)}`).join(' ');
    const area = `${line} L${this.width},${this.height} L0,${this.height} Z`;

    return {
      line,
      area,
      lastX: xs[xs.length - 1],
      lastY: ys[ys.length - 1]
    };
  });
}
