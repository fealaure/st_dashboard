<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SaveState\Guides\Application\IngestGuidesUseCase;

class IngestGuidesCommand extends Command
{
    protected $signature = 'guides:ingest';

    protected $description = 'Baixa guias dos feeds RSS dedicados e persiste itens novos.';

    public function handle(IngestGuidesUseCase $useCase): int
    {
        $this->info('Iniciando ingestão de guias...');

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
