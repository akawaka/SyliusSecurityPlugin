<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\Command;

use AKAWAKA\SyliusSecurityPlugin\Entity\AdminActionLogInterface;
use AKAWAKA\SyliusSecurityPlugin\Repository\AdminActionLogRepositoryInterface;
use Psr\Clock\ClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'akawaka:security:purge-admin-action-logs',
    description: 'Deletes the administrator action logs older than the given number of days.',
)]
final class PurgeAdminActionLogsCommand extends Command
{
    /** @param AdminActionLogRepositoryInterface<AdminActionLogInterface> $adminActionLogRepository */
    public function __construct(
        private readonly AdminActionLogRepositoryInterface $adminActionLogRepository,
        private readonly ClockInterface $clock,
        private readonly int $retentionDays,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('days', 'd', InputOption::VALUE_REQUIRED, 'Logs older than this number of days are deleted', (string) $this->retentionDays);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = $input->getOption('days');

        if (!is_string($days) || !ctype_digit($days) || 1 > (int) $days) {
            $io->error('The "--days" option must be a positive integer.');

            return Command::INVALID;
        }

        $date = $this->clock->now()->modify(sprintf('-%d days', (int) $days));
        $deleted = $this->adminActionLogRepository->deleteOlderThan($date);

        $io->success(sprintf('%d administrator action log(s) older than %d day(s) deleted.', $deleted, (int) $days));

        return Command::SUCCESS;
    }
}
