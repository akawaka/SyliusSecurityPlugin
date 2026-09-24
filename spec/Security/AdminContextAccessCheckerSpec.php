<?php

declare(strict_types=1);

namespace spec\AKAWAKA\SyliusSecurityPlugin\Security;

use AKAWAKA\SyliusSecurityPlugin\Security\AdminContext;
use PhpSpec\ObjectBehavior;
use Sylius\Component\Core\Model\AdminUserInterface;

final class AdminContextAccessCheckerSpec extends ObjectBehavior
{
    function let(): void
    {
        $this->beConstructedWith('ROLE_SUPER_ADMIN');
    }

    function it_grants_everything_to_super_admins(AdminUserInterface $user): void
    {
        $user->getRoles()->willReturn(['ROLE_ADMINISTRATION_ACCESS', 'ROLE_SUPER_ADMIN']);
        $context = new AdminContext('catalog', ['sylius_admin_product']);

        $this->isSuperAdmin($user)->shouldReturn(true);
        $this->canRead($user, $context)->shouldReturn(true);
        $this->canWrite($user, $context)->shouldReturn(true);
    }

    function it_denies_by_default(AdminUserInterface $user): void
    {
        $user->getRoles()->willReturn(['ROLE_ADMINISTRATION_ACCESS']);
        $context = new AdminContext('catalog', ['sylius_admin_product']);

        $this->isSuperAdmin($user)->shouldReturn(false);
        $this->canRead($user, $context)->shouldReturn(false);
        $this->canWrite($user, $context)->shouldReturn(false);
    }

    function it_grants_read_only_with_the_read_role(AdminUserInterface $user): void
    {
        $user->getRoles()->willReturn(['ROLE_ADMINISTRATION_ACCESS', 'ROLE_ADMIN_CATALOG_READ']);
        $context = new AdminContext('catalog', ['sylius_admin_product']);

        $this->canRead($user, $context)->shouldReturn(true);
        $this->canWrite($user, $context)->shouldReturn(false);
        $this->canRead($user, new AdminContext('sales', ['sylius_admin_order']))->shouldReturn(false);
    }

    function it_grants_read_with_the_write_role(AdminUserInterface $user): void
    {
        $user->getRoles()->willReturn(['ROLE_ADMINISTRATION_ACCESS', 'ROLE_ADMIN_CATALOG_WRITE']);
        $context = new AdminContext('catalog', ['sylius_admin_product']);

        $this->canRead($user, $context)->shouldReturn(true);
        $this->canWrite($user, $context)->shouldReturn(true);
    }
}
