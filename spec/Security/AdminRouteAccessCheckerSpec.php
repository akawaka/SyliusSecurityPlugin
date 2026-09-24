<?php

declare(strict_types=1);

namespace spec\AKAWAKA\SyliusSecurityPlugin\Security;

use AKAWAKA\SyliusSecurityPlugin\Security\AdminContextAccessChecker;
use AKAWAKA\SyliusSecurityPlugin\Security\AdminContextRegistry;
use PhpSpec\ObjectBehavior;
use Sylius\Component\Core\Model\AdminUserInterface;

final class AdminRouteAccessCheckerSpec extends ObjectBehavior
{
    function let(): void
    {
        $this->beConstructedWith(
            new AdminContextRegistry(['sylius_admin_dashboard'], ['catalog' => ['route_prefixes' => ['sylius_admin_product']]]),
            new AdminContextAccessChecker('ROLE_SUPER_ADMIN'),
        );
    }

    function it_always_grants_public_routes_and_non_sylius_admin_routes(AdminUserInterface $user): void
    {
        $user->getRoles()->willReturn(['ROLE_ADMINISTRATION_ACCESS']);

        $this->isGranted($user, 'sylius_admin_dashboard', false)->shouldReturn(true);
        $this->isGranted($user, 'app_admin_custom_index', false)->shouldReturn(true);
    }

    function it_reserves_unmapped_sylius_admin_routes_to_super_admins(AdminUserInterface $user, AdminUserInterface $superAdmin): void
    {
        $user->getRoles()->willReturn(['ROLE_ADMINISTRATION_ACCESS', 'ROLE_ADMIN_CATALOG_WRITE']);
        $superAdmin->getRoles()->willReturn(['ROLE_ADMINISTRATION_ACCESS', 'ROLE_SUPER_ADMIN']);

        $this->isGranted($user, 'sylius_admin_order_index', true)->shouldReturn(false);
        $this->isGranted($superAdmin, 'sylius_admin_order_index', true)->shouldReturn(true);
    }

    function it_checks_the_context_role_depending_on_the_access_mode(AdminUserInterface $user): void
    {
        $user->getRoles()->willReturn(['ROLE_ADMINISTRATION_ACCESS', 'ROLE_ADMIN_CATALOG_READ']);

        $this->isGranted($user, 'sylius_admin_product_index', true)->shouldReturn(true);
        $this->isGranted($user, 'sylius_admin_product_create', false)->shouldReturn(false);
    }
}
