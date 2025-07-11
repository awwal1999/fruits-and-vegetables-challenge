<?php

namespace App\Command;

use App\Service\FruitVegetableService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ProcessRequestCommand extends Command
{
    protected static $defaultName = 'app:process-request';
    protected static $defaultDescription = 'Process the request.json file and store data in CSV';

    private FruitVegetableService $service;

    public function __construct(FruitVegetableService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            if (!file_exists('request.json')) {
                $io->error('request.json file not found');
                return Command::FAILURE;
            }

            $requestData = json_decode(file_get_contents('request.json'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $io->error('Invalid JSON in request.json');
                return Command::FAILURE;
            }

            $result = $this->service->processRequest($requestData);

            $io->success('Data processed successfully!');
            $io->table(['Metric', 'Value'], [
                ['Fruits Count', $result['fruits_count']],
                ['Vegetables Count', $result['vegetables_count']],
                ['Total Processed', $result['total_processed']],
            ]);

            $io->info('Data has been saved to CSV files in var/data/');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 