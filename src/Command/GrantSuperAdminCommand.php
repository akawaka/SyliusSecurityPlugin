<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\Command;

use Doctrine\Persistence\ObjectManager;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\User\Repository\UserRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'akawaka:security:grant-super-admin',
    description: 'Grants the super admin role to an administrator.',
)]
final class GrantSuperAdminCommand extends Command
{
    /** @param UserRepositoryInterface<AdminUserInterface> $adminUserRepository */
    public function __construct(
        private readonly UserRepositoryInterface $adminUserRepository,
        private readonly ObjectManager $adminUserManager,
        private readonly string $superAdminRole,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'Email of the administrator');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        /** @var string $email */
        $email = $input->getArgument('email');

        $adminUser = $this->adminUserRepository->findOneByEmail($email);
        if (!$adminUser instanceof AdminUserInterface) {
            $io->error(sprintf('No administrator found with email "%s".', $email));

            return Command::FAILURE;
        }

        $adminUser->addRole($this->superAdminRole);
        $this->adminUserManager->flush();

        $io->success(sprintf('Administrator "%s" has been granted %s.', $email, $this->superAdminRole));

        return Command::SUCCESS;
    }
}
