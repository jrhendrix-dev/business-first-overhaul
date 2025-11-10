<?php
declare(strict_types=1);

namespace App\Command;

use App\Repository\ClassroomRepository;
use App\Repository\UserRepository;
use App\Service\PurchaseClassManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'demo:stripe-check')]
final class DemoStripeCheckCommand extends Command
{
    public function __construct(
        private readonly PurchaseClassManager $manager,
        private readonly UserRepository $users,
        private readonly ClassroomRepository $classes
    ) { parent::__construct(); }

    protected function configure(): void
    {
        $this
            ->addArgument('studentId', InputArgument::REQUIRED)
            ->addArgument('classId', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $studentId = (int)$input->getArgument('studentId');
        $classId   = (int)$input->getArgument('classId');

        try {
            $res = $this->manager->createCheckoutSession(
                $studentId, $classId, 'http://localhost:8080/payment/success', 'http://localhost:8080/payment/cancel'
            );
            $io->success('Checkout URL: '.$res['checkoutUrl']);
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
