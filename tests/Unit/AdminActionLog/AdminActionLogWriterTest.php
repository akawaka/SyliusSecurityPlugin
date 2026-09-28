<?php

declare(strict_types=1);

namespace Tests\AKAWAKA\SyliusSecurityPlugin\Unit\AdminActionLog;

use AKAWAKA\SyliusSecurityPlugin\AdminActionLog\AdminActionLogWriter;
use AKAWAKA\SyliusSecurityPlugin\Entity\AdminActionLog;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Sylius\Component\Core\Model\AdminUser;
use Tests\AKAWAKA\SyliusSecurityPlugin\Unit\AdminUserTrait;

final class AdminActionLogWriterTest extends TestCase
{
    use AdminUserTrait;

    public function test_it_inserts_the_log_without_going_through_the_unit_of_work(): void
    {
        $adminUser = $this->createAdminUser(3);
        $log = new AdminActionLog();
        $log->setType('write');
        $log->setAdminUser($adminUser);
        $log->setRouteParameters(['id' => '1']);

        $connection = $this->createConnection();
        $connection
            ->expects(self::once())
            ->method('insert')
            ->with(
                '"akawaka_admin_action_log"',
                ['"type"' => 'write', '"route_parameters"' => ['id' => '1'], '"admin_user_id"' => 3],
                ['"type"' => 'string', '"route_parameters"' => 'json'],
            )
        ;

        $entityManager = $this->createEntityManager($connection, $this->createLogMetadata($log), $adminUser);
        $entityManager->expects(self::never())->method('persist');
        $entityManager->expects(self::never())->method('flush');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('error');

        (new AdminActionLogWriter($entityManager, $logger))->write($log);
    }

    public function test_it_inserts_a_null_administrator_when_the_log_has_none(): void
    {
        $log = new AdminActionLog();
        $log->setType('login_failure');

        $connection = $this->createConnection();
        $connection
            ->expects(self::once())
            ->method('insert')
            ->with(
                self::anything(),
                ['"type"' => 'login_failure', '"route_parameters"' => [], '"admin_user_id"' => null],
                self::anything(),
            )
        ;

        (new AdminActionLogWriter(
            $this->createEntityManager($connection, $this->createLogMetadata($log), null),
            $this->createMock(LoggerInterface::class),
        ))->write($log);
    }

    public function test_it_logs_the_error_instead_of_breaking_the_request(): void
    {
        $exception = new \RuntimeException('Database is down');

        $connection = $this->createConnection();
        $connection->method('insert')->willThrowException($exception);

        $log = new AdminActionLog();
        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('error')
            ->with('Unable to save the administrator action log: {message}', ['message' => 'Database is down', 'exception' => $exception])
        ;

        (new AdminActionLogWriter($this->createEntityManager($connection, $this->createLogMetadata($log), null), $logger))->write($log);
    }

    private function createConnection(): Connection
    {
        $connection = $this->createMock(Connection::class);
        $connection->method('quoteIdentifier')->willReturnCallback(static fn (string $identifier): string => sprintf('"%s"', $identifier));

        return $connection;
    }

    /** @return ClassMetadata<AdminActionLog> */
    private function createLogMetadata(AdminActionLog $log): ClassMetadata
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->method('getTableName')->willReturn('akawaka_admin_action_log');
        $metadata->method('getFieldNames')->willReturn(['id', 'type', 'routeParameters']);
        $metadata->method('isIdentifier')->willReturnCallback(static fn (string $field): bool => 'id' === $field);
        $metadata->method('getColumnName')->willReturnMap([['type', 'type'], ['routeParameters', 'route_parameters']]);
        $metadata->method('getTypeOfField')->willReturnMap([['type', 'string'], ['routeParameters', 'json']]);
        $metadata->method('getAssociationNames')->willReturn(['adminUser']);
        $metadata->method('isSingleValuedAssociation')->willReturn(true);
        $metadata->method('isAssociationWithSingleJoinColumn')->willReturn(true);
        $metadata->method('getSingleAssociationJoinColumnName')->with('adminUser')->willReturn('admin_user_id');
        $metadata->method('getFieldValue')->willReturnCallback(static fn (object $entity, string $field): mixed => match ($field) {
            'type' => $log->getType(),
            'routeParameters' => $log->getRouteParameters(),
            'adminUser' => $log->getAdminUser(),
            default => throw new \LogicException(sprintf('Unexpected field "%s".', $field)),
        });

        return $metadata;
    }

    /** @param ClassMetadata<AdminActionLog> $logMetadata */
    private function createEntityManager(Connection $connection, ClassMetadata $logMetadata, ?AdminUser $adminUser): EntityManagerInterface
    {
        $adminUserMetadata = $this->createMock(ClassMetadata::class);
        $adminUserMetadata->method('getIdentifierValues')->willReturn(null === $adminUser ? [] : ['id' => $adminUser->getId()]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getConnection')->willReturn($connection);
        $entityManager->method('getClassMetadata')->willReturnMap([
            [AdminActionLog::class, $logMetadata],
            [AdminUser::class, $adminUserMetadata],
        ]);

        return $entityManager;
    }
}
