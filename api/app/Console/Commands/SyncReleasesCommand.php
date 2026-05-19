<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SaveState\Releases\Application\SyncReleasesUseCase;

class SyncReleasesCommand extends Command
{
    protected $signature = 'releases:sync {--days=90 : Quantos dias à frente buscar} {--limit=500 : Máximo de jogos por rodada}';

    protected $description = 'Sincroniza lançamentos próximos do IGDB pras plataformas principais.';

    public function handle(SyncReleasesUseCase $useCase): int
    {
        $days = (int) $this->option('days');
        $limit = (int) $this->option('limit');

        $this->info(sprintf('Sincronizando lançamentos dos próximos %d dias (limite %d)...', $days, $limit));

        $result = $useCase->execute($days, $limit);

        $this->line(sprintf(
            '  retornados pelo IGDB: %d  |  persistidos: %d',
            $result->fetched,
            $result->upserted,
        ));

        return self::SUCCESS;
    }
}
