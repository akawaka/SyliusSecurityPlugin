<?php

declare(strict_types=1);

namespace Tests\AKAWAKA\SyliusSecurityPlugin\Unit\Form\Extension;

use AKAWAKA\SyliusSecurityPlugin\Form\Extension\AdminUserTypeExtension;
use AKAWAKA\SyliusSecurityPlugin\Security\AdminRoleChoicesProviderInterface;
use AKAWAKA\SyliusSecurityPlugin\Security\AdminRouteAccessCheckerInterface;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\Form\Type\User\AdminUserType;
use Sylius\Component\Core\Model\AdminUser;
use Sylius\Component\Core\Model\AdminUserInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Tests\AKAWAKA\SyliusSecurityPlugin\Unit\AdminUserTrait;

final class AdminUserTypeExtensionTest extends TestCase
{
    use AdminUserTrait;

    private const CHOICES = [
        'akawaka_sylius_security.ui.context.catalog' => [
            'akawaka_sylius_security.ui.access.read' => 'ROLE_ADMIN_CATALOG_READ',
            'akawaka_sylius_security.ui.access.write' => 'ROLE_ADMIN_CATALOG_WRITE',
        ],
    ];

    private const MANAGED_ROLES = ['ROLE_ADMIN_CATALOG_READ', 'ROLE_ADMIN_CATALOG_WRITE'];

    public function test_it_extends_the_admin_user_form(): void
    {
        self::assertSame([AdminUserType::class], [...AdminUserTypeExtension::getExtendedTypes()]);
    }

    public function test_it_does_not_add_the_roles_field_for_administrators_who_cannot_update_administrators(): void
    {
        $form = $this->createForm($this->createAdminUser(2), false);

        self::assertFalse($form->has('securityRoles'));
    }

    public function test_it_adds_the_roles_field_checking_the_managed_roles_of_the_administrator(): void
    {
        $adminUser = $this->createAdminUser(2, ['ROLE_ADMINISTRATION_ACCESS', 'ROLE_ADMIN_CATALOG_READ']);

        $form = $this->createForm($adminUser, true);

        self::assertTrue($form->has('securityRoles'));
        self::assertSame(['ROLE_ADMIN_CATALOG_READ'], $form->get('securityRoles')->getData());
        self::assertSame(self::CHOICES, $form->get('securityRoles')->getConfig()->getOption('choices'));
    }

    public function test_it_replaces_the_managed_roles_with_the_submitted_ones(): void
    {
        $adminUser = $this->createAdminUser(2, ['ROLE_ADMINISTRATION_ACCESS', 'ROLE_API_ACCESS', 'ROLE_ADMIN_CATALOG_READ']);

        $form = $this->createForm($adminUser, true);
        $form->submit(['securityRoles' => ['ROLE_ADMIN_CATALOG_WRITE']]);

        self::assertTrue($form->isSynchronized());
        self::assertFalse($adminUser->hasRole('ROLE_ADMIN_CATALOG_READ'));
        self::assertTrue($adminUser->hasRole('ROLE_ADMIN_CATALOG_WRITE'));
        self::assertTrue($adminUser->hasRole('ROLE_ADMINISTRATION_ACCESS'));
        self::assertTrue($adminUser->hasRole('ROLE_API_ACCESS'));
    }

    public function test_it_removes_every_managed_role_when_none_is_submitted(): void
    {
        $adminUser = $this->createAdminUser(2, ['ROLE_ADMINISTRATION_ACCESS', 'ROLE_ADMIN_CATALOG_WRITE']);

        $form = $this->createForm($adminUser, true);
        $form->submit([]);

        self::assertSame(['ROLE_ADMINISTRATION_ACCESS'], array_values(array_diff($adminUser->getRoles(), ['ROLE_USER'])));
    }

    private function createForm(AdminUserInterface $adminUser, bool $canUpdateAdministrators): FormInterface
    {
        $grantor = $this->createAdminUser(1, ['ROLE_SUPER_ADMIN']);

        $routeAccessChecker = $this->createMock(AdminRouteAccessCheckerInterface::class);
        $routeAccessChecker
            ->method('isGranted')
            ->with($grantor, 'sylius_admin_admin_user_update', false)
            ->willReturn($canUpdateAdministrators)
        ;

        $roleChoicesProvider = $this->createMock(AdminRoleChoicesProviderInterface::class);
        $roleChoicesProvider->method('getChoices')->with($grantor)->willReturn(self::CHOICES);
        $roleChoicesProvider->method('getRoles')->with($grantor)->willReturn(self::MANAGED_ROLES);

        $builder = Forms::createFormFactory()->createBuilder(FormType::class, $adminUser, ['data_class' => AdminUser::class]);

        (new AdminUserTypeExtension($roleChoicesProvider, $routeAccessChecker, $this->createTokenStorage($grantor)))->buildForm($builder, []);

        return $builder->getForm();
    }
}
