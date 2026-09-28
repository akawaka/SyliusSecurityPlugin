<?php

declare(strict_types=1);

namespace Tests\AKAWAKA\SyliusSecurityPlugin\Unit\Command;

use AKAWAKA\SyliusSecurityPlugin\Command\GrantSuperAdminCommand;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\TestCase;
use Sylius\Component\User\Repository\UserRepositoryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\AKAWAKA\SyliusSecurityPlugin\Unit\AdminUserTrait;

final class GrantSuperAdminCommandTest extends TestCase
{
    use AdminUserTrait;

    public function test_it_grants_the_super_admin_role_to_the_administrator(): void
    {
        $adminUser = $this->createAdminUser(1, ['ROLE_ADMINISTRATION_ACCESS']);

        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->method('findOneByEmail')->with('admin@example.com')->willReturn($adminUser);
        $manager = $this->createMock(ObjectManager::class);
        $manager->expects(self::once())->method('flush');

        $commandTester = new CommandTester(new GrantSuperAdminCommand($repository, $manager, 'ROLE_ROOT'));

        self::assertSame(Command::SUCCESS, $commandTester->execute(['email' => 'admin@example.com']));
        self::assertTrue($adminUser->hasRole('ROLE_ROOT'));
        self::assertTrue($adminUser->hasRole('ROLE_ADMINISTRATION_ACCESS'));
        self::assertStringContainsString('Administrator "admin@example.com" has been granted ROLE_ROOT.', $commandTester->getDisplay());
    }

    public function test_it_fails_when_the_administrator_does_not_exist(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->method('findOneByEmail')->willReturn(null);
        $manager = $this->createMock(ObjectManager::class);
        $manager->expects(self::never())->method('flush');

        $commandTester = new CommandTester(new GrantSuperAdminCommand($repository, $manager, 'ROLE_SUPER_ADMIN'));

        self::assertSame(Command::FAILURE, $commandTester->execute(['email' => 'unknown@example.com']));
        self::assertStringContainsString('No administrator found with email "unknown@example.com".', $commandTester->getDisplay());
    }
}
