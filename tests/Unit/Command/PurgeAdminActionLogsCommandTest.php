<?php

declare(strict_types=1);

namespace Tests\AKAWAKA\SyliusSecurityPlugin\Unit\Command;

use AKAWAKA\SyliusSecurityPlugin\Command\PurgeAdminActionLogsCommand;
use AKAWAKA\SyliusSecurityPlugin\Repository\AdminActionLogRepositoryInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class PurgeAdminActionLogsCommandTest extends TestCase
{
    public function test_it_deletes_the_logs_older_than_the_retention_period_by_default(): void
    {
        $repository = $this->createMock(AdminActionLogRepositoryInterface::class);
        $repository
            ->expects(self::once())
            ->method('deleteOlderThan')
            ->with(new \DateTimeImmutable('2026-06-30 12:00:00'))
            ->willReturn(12)
        ;

        $commandTester = $this->createCommandTester($repository);

        self::assertSame(Command::SUCCESS, $commandTester->execute([]));
        self::assertStringContainsString('12 administrator action log(s) older than 90 day(s) deleted.', $commandTester->getDisplay());
    }

    public function test_it_deletes_the_logs_older_than_the_given_number_of_days(): void
    {
        $repository = $this->createMock(AdminActionLogRepositoryInterface::class);
        $repository
            ->expects(self::once())
            ->method('deleteOlderThan')
            ->with(new \DateTimeImmutable('2026-09-21 12:00:00'))
            ->willReturn(0)
        ;

        $commandTester = $this->createCommandTester($repository);

        self::assertSame(Command::SUCCESS, $commandTester->execute(['--days' => '7']));
    }

    #[DataProvider('provideInvalidDays')]
    public function test_it_rejects_an_invalid_number_of_days(string $days): void
    {
        $repository = $this->createMock(AdminActionLogRepositoryInterface::class);
        $repository->expects(self::never())->method('deleteOlderThan');

        $commandTester = $this->createCommandTester($repository);

        self::assertSame(Command::INVALID, $commandTester->execute(['--days' => $days]));
        self::assertStringContainsString('The "--days" option must be a positive integer.', $commandTester->getDisplay());
    }

    /** @return iterable<string, array{string}> */
    public static function provideInvalidDays(): iterable
    {
        yield 'zero' => ['0'];
        yield 'negative' => ['-5'];
        yield 'not a number' => ['abc'];
        yield 'decimal' => ['1.5'];
    }

    private function createCommandTester(AdminActionLogRepositoryInterface $repository): CommandTester
    {
        return new CommandTester(new PurgeAdminActionLogsCommand(
            $repository,
            new MockClock(new \DateTimeImmutable('2026-09-28 12:00:00')),
            90,
        ));
    }
}
