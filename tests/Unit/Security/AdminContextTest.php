<?php

declare(strict_types=1);

namespace Tests\AKAWAKA\SyliusSecurityPlugin\Unit\Security;

use AKAWAKA\SyliusSecurityPlugin\Security\AdminContext;
use PHPUnit\Framework\TestCase;

final class AdminContextTest extends TestCase
{
    public function test_it_exposes_its_code_and_route_prefixes(): void
    {
        $context = new AdminContext('catalog', ['sylius_admin_product', 'sylius_admin_taxon']);

        self::assertSame('catalog', $context->getCode());
        self::assertSame(['sylius_admin_product', 'sylius_admin_taxon'], $context->getRoutePrefixes());
    }

    public function test_it_derives_its_roles_from_its_code(): void
    {
        $context = new AdminContext('customer_service', []);

        self::assertSame('ROLE_ADMIN_CUSTOMER_SERVICE_READ', $context->getReadRole());
        self::assertSame('ROLE_ADMIN_CUSTOMER_SERVICE_WRITE', $context->getWriteRole());
    }
}
