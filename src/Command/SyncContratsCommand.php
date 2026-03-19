<?php

namespace App\Command;

use App\Service\ContratSyncService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync-contrats',
    description: 'Synchronise les contrats depuis l\'API externe',
)]
class SyncContratsCommand extends Command
{
    public function __construct(
        private ContratSyncService $contratSyncService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Synchronisation des contrats');

        try {
            $contrats = $this->contratSyncService->syncAndGetContrats();
            $io->success(sprintf('Synchronisation terminee : %d contrats', count($contrats)));
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
