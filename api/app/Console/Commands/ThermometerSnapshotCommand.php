<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SaveState\News\Application\SnapshotThermometersUseCase;

class ThermometerSnapshotCommand extends Command
{
    protected $signature = 'thermometer:snapshot';

    protected $description = 'Recalcula o termômetro dos clusters recentes e grava um snapshot histórico.';

    public function handle(SnapshotThermometersUseCase $useCase): int
    {
        $this->info('Capturando snapshot dos termômetros...');
        $count = $useCase->execute();
        $this->info(sprintf('Gravados %d snapshots.', $count));

        return self::SUCCESS;
    }
}
