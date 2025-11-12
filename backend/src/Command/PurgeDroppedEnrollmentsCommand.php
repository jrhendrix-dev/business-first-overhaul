<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\EnrollmentManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Purges DROPPED enrollments older than N days (default 90).
 *
 * Usage:
 *  php bin/console app:purge:dropped-enrollments
 *  php bin/console app:purge:dropped-enrollments 120
 */
#[AsCommand(name: 'app:purge:dropped-enrollments', description: 'Purge old dropped enrollments')]
final class PurgeDroppedEnrollmentsCommand extends Command
{
    public function __construct(private readonly EnrollmentManager $enrollments)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('days', InputArgument::OPTIONAL, 'Retention window in days', '90');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $days = (int) $input->getArgument('days');
        $before = (new \DateTimeImmutable())->sub(new \DateInterval(sprintf('P%dD', max(1, $days))));
        $count = $this->enrollments->purgeDroppedOlderThan($before);

        $output->writeln(sprintf('Purged %d dropped enrollments older than %s.', $count, $before->format(\DateTimeInterface::ATOM)));
        return Command::SUCCESS;
    }
}
