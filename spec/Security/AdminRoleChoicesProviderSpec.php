<?php

declare(strict_types=1);

namespace spec\AKAWAKA\SyliusSecurityPlugin\Security;

use AKAWAKA\SyliusSecurityPlugin\Security\AdminContextAccessChecker;
use AKAWAKA\SyliusSecurityPlugin\Security\AdminContextRegistry;
use PhpSpec\ObjectBehavior;
use Sylius\Component\Core\Model\AdminUserInterface;

final class AdminRoleChoicesProviderSpec extends ObjectBehavior
{
    function let(): void
    {
        $this->beConstructedWith(
            new AdminContextRegistry([], ['catalog' => ['route_prefixes' => ['sylius_admin_product']]]),
            new AdminContextAccessChecker('ROLE_SUPER_ADMIN'),
            'ROLE_SUPER_ADMIN',
        );
    }

    function it_provides_context_roles_grouped_by_context(AdminUserInterface $grantor): void
    {
        $grantor->getRoles()->willReturn(['ROLE_ADMINISTRATION_ACCESS', 'ROLE_ADMIN_ADMINISTRATION_WRITE']);

        $this->getChoices($grantor)->shouldReturn([
            'akawaka_sylius_security.ui.context.catalog' => [
                'akawaka_sylius_security.ui.role.read' => 'ROLE_ADMIN_CATALOG_READ',
                'akawaka_sylius_security.ui.role.write' => 'ROLE_ADMIN_CATALOG_WRITE',
            ],
        ]);
        $this->getRoles($grantor)->shouldReturn(['ROLE_ADMIN_CATALOG_READ', 'ROLE_ADMIN_CATALOG_WRITE']);
    }

    function it_lets_only_super_admins_grant_super_admin(AdminUserInterface $grantor): void
    {
        $grantor->getRoles()->willReturn(['ROLE_ADMINISTRATION_ACCESS', 'ROLE_SUPER_ADMIN']);

        $this->getRoles($grantor)->shouldReturn(['ROLE_SUPER_ADMIN', 'ROLE_ADMIN_CATALOG_READ', 'ROLE_ADMIN_CATALOG_WRITE']);
    }
}
