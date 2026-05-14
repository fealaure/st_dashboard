<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SaveState\News\Application\FetchRedditSignalsUseCase;

class RedditSyncCommand extends Command
{
    protected $signature = 'reddit:sync {--max=40 : Máximo de clusters a processar nesta rodada}';

    protected $description = 'Busca posts do Reddit que linkam aos clusters recentes e atualiza upvotes/comentários.';

    public function handle(FetchRedditSignalsUseCase $useCase): int
    {
        $max = (int) $this->option('max');
        $this->info(sprintf('Sincronizando até %d clusters com o Reddit...', $max));

        $result = $useCase->execute($max);

        $this->line(sprintf(
            '  clusters processados: %d  |  signals capturados: %d',
            $result->clustersProcessed,
            $result->signalsCaptured,
        ));

        if ($result->errors !== []) {
            $this->warn('Erros durante a sincronização:');
            foreach ($result->errors as $error) {
                $this->line('  - '.$error);
            }
        }

        return self::SUCCESS;
    }
}
