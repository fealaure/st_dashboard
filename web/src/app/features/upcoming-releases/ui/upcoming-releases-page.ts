import { Component, inject } from '@angular/core';

import { ReleasesFacade } from '../application/releases-facade';

@Component({
  selector: 'ss-upcoming-releases-page',
  imports: [],
  template: `
    <section class="releases-page">
      <header class="releases-page__header">
        <h2>Próximos lançamentos</h2>
        <p>Calendário de lançamentos pra apoiar pedidos de review key.</p>
      </header>
      <div class="releases-page__placeholder">
        <span class="releases-page__dot"></span>
        <p>Integração com IGDB ainda não implementada — Fase 4.</p>
      </div>
    </section>
  `,
  styles: [
    `
      .releases-page {
        display: flex;
        flex-direction: column;
        gap: 16px;
      }

      .releases-page__header h2 {
        margin: 0 0 4px;
        font-size: 20px;
        font-weight: 600;
      }

      .releases-page__header p {
        margin: 0;
        color: var(--ss-text-muted);
      }

      .releases-page__placeholder {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 16px;
        background: var(--ss-bg-surface);
        border: 1px solid var(--ss-border);
        border-radius: var(--ss-radius-md);
      }

      .releases-page__placeholder .releases-page__dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: var(--ss-accent);
        box-shadow: 0 0 12px var(--ss-accent);
      }

      .releases-page__placeholder p {
        margin: 0;
        color: var(--ss-text-muted);
      }
    `
  ]
})
export class UpcomingReleasesPage {
  protected readonly facade = inject(ReleasesFacade);
}
