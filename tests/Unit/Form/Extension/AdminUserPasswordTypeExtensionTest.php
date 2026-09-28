<?php

declare(strict_types=1);

namespace Tests\AKAWAKA\SyliusSecurityPlugin\Unit\Form\Extension;

use AKAWAKA\SyliusSecurityPlugin\Form\Extension\AdminUserPasswordTypeExtension;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\Form\Type\User\AdminUserType;
use Sylius\Component\Core\Model\AdminUser;
use Sylius\Component\Core\Model\AdminUserInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Tests\AKAWAKA\SyliusSecurityPlugin\Unit\AdminUserTrait;

final class AdminUserPasswordTypeExtensionTest extends TestCase
{
    use AdminUserTrait;

    public function test_it_extends_the_admin_user_form(): void
    {
        self::assertSame([AdminUserType::class], [...AdminUserPasswordTypeExtension::getExtendedTypes()]);
    }

    public function test_it_keeps_the_password_field_on_the_administrator_own_account(): void
    {
        $form = $this->createForm($this->createAdminUser(1), $this->createAdminUser(1));

        self::assertTrue($form->has('plainPassword'));
    }

    public function test_it_keeps_the_password_field_when_creating_an_administrator(): void
    {
        $form = $this->createForm($this->createAdminUser(null), $this->createAdminUser(1));

        self::assertTrue($form->has('plainPassword'));
    }

    public function test_it_removes_the_password_field_on_another_administrator_account(): void
    {
        $form = $this->createForm($this->createAdminUser(2), $this->createAdminUser(1, ['ROLE_SUPER_ADMIN']));

        self::assertFalse($form->has('plainPassword'));
    }

    public function test_it_removes_the_password_field_without_authenticated_administrator(): void
    {
        $form = $this->createForm($this->createAdminUser(2), null);

        self::assertFalse($form->has('plainPassword'));
    }

    private function createForm(AdminUserInterface $adminUser, ?AdminUserInterface $currentUser): FormInterface
    {
        $builder = Forms::createFormFactory()
            ->createBuilder(FormType::class, $adminUser, ['data_class' => AdminUser::class])
            ->add('plainPassword', PasswordType::class)
        ;

        (new AdminUserPasswordTypeExtension($this->createTokenStorage($currentUser)))->buildForm($builder, []);

        return $builder->getForm();
    }
}
