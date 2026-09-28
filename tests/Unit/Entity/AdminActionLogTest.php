<?php

declare(strict_types=1);

namespace Tests\AKAWAKA\SyliusSecurityPlugin\Unit\Entity;

use AKAWAKA\SyliusSecurityPlugin\Entity\AdminActionLog;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\AdminUser;

final class AdminActionLogTest extends TestCase
{
    public function test_it_is_dated_at_creation(): void
    {
        $before = new \DateTimeImmutable();
        $log = new AdminActionLog();

        self::assertNull($log->getId());
        self::assertGreaterThanOrEqual($before, $log->getCreatedAt());
        self::assertLessThanOrEqual(new \DateTimeImmutable(), $log->getCreatedAt());
        self::assertSame([], $log->getRouteParameters());
        self::assertNull($log->getAdminUser());
        self::assertNull($log->getStatusCode());
    }

    public function test_it_stores_the_action_details(): void
    {
        $adminUser = new AdminUser();
        $createdAt = new \DateTimeImmutable('2026-09-24 12:00:00');

        $log = new AdminActionLog();
        $log->setCreatedAt($createdAt);
        $log->setType('write');
        $log->setAdminUser($adminUser);
        $log->setAdminEmail('admin@example.com');
        $log->setRoute('sylius_admin_product_update');
        $log->setContext('catalog');
        $log->setMethod('PUT');
        $log->setUri('/admin/products/1');
        $log->setRouteParameters(['id' => '1']);
        $log->setStatusCode(302);
        $log->setIp('127.0.0.1');
        $log->setUserAgent('Mozilla/5.0');

        self::assertSame($createdAt, $log->getCreatedAt());
        self::assertSame('write', $log->getType());
        self::assertSame($adminUser, $log->getAdminUser());
        self::assertSame('admin@example.com', $log->getAdminEmail());
        self::assertSame('sylius_admin_product_update', $log->getRoute());
        self::assertSame('catalog', $log->getContext());
        self::assertSame('PUT', $log->getMethod());
        self::assertSame('/admin/products/1', $log->getUri());
        self::assertSame(['id' => '1'], $log->getRouteParameters());
        self::assertSame(302, $log->getStatusCode());
        self::assertSame('127.0.0.1', $log->getIp());
        self::assertSame('Mozilla/5.0', $log->getUserAgent());
    }
}
