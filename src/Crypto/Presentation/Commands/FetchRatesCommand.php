<?php

declare(strict_types=1);

namespace Rates\Crypto\Presentation\Commands;

use Rates\Crypto\Domain\Services\RateService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'crypto:rates:fetch',
    description: 'Fetch cryptocurrency rates from External API'
)]
class FetchRatesCommand extends Command
{
    /**
     * @param list<string> $pairs
     */
    public function __construct(
        private readonly RateService $rateService,
        #[Autowire(param: 'crypto.pairs')]
        private readonly array $pairs,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            name: 'batch-count',
            mode: InputOption::VALUE_OPTIONAL,
            description: 'How many items to process per batch (must be a positive integer).',
            default: 100,
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->info('Starting rate fetching...');

        $raw = $input->getOption('batch-count');
        if (!is_numeric($raw) || (int) $raw <= 0) {
            $io->error('Option --batch-count must be a positive integer.');

            return Command::INVALID;
        }

        $batchCount = (int) $raw;

        $io->writeln(sprintf('Batch size: <info>%d</info>', $batchCount));

        $this->rateService->fetchRates($this->pairs, $batchCount);

        $io->success('Rates fetched successfully!');

        return Command::SUCCESS;
    }
}
