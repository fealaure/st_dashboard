<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SaveState\News\Application\ReclusterAllUseCase;

class ReclusterNewsCommand extends Command
{
    protected $signature = 'news:recluster';

    protected $description = 'Clusteriza todos os news_items que ainda não estão atribuídos a um cluster.';

    public function handle(ReclusterAllUseCase $useCase): int
    {
        $this->info('Reclusterizando notícias sem cluster...');
        $count = $useCase->execute();
        $this->info(sprintf('Atribuídas %d notícias a clusters.', $count));

        return self::SUCCESS;
    }
}
