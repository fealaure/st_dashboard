<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SaveState\News\Application\IngestNewsUseCase;

class IngestNewsCommand extends Command
{
    protected $signature = 'news:ingest';

    protected $description = 'Baixa as notícias dos feeds RSS configurados e persiste os itens novos.';

    public function handle(IngestNewsUseCase $useCase): int
    {
        $this->info('Iniciando ingestão de notícias...');

        $result = $useCase->execute();

        $this->line(sprintf(
            '  itens lidos: %d  |  itens inseridos: %d',
            $result->itemsFetched,
            $result->itemsInserted,
        ));

        if ($result->errors !== []) {
            $this->warn('Erros durante a ingestão:');
            foreach ($result->errors as $error) {
                $this->line('  - '.$error);
            }
        }

        return self::SUCCESS;
    }
}
