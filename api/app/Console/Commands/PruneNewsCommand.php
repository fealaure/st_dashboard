<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SaveState\News\Application\PruneOldNewsUseCase;

class PruneNewsCommand extends Command
{
    protected $signature = 'news:prune {--days=30 : Retenção em dias}';

    protected $description = 'Remove notícias com published_at mais antigo que o número de dias informado.';

    public function handle(PruneOldNewsUseCase $useCase): int
    {
        $days = (int) $this->option('days');
        $deleted = $useCase->execute($days);

        $this->info(sprintf('Removidas %d notícias com mais de %d dias.', $deleted, $days));

        return self::SUCCESS;
    }
}
