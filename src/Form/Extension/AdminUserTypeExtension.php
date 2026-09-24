<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\Form\Extension;

use AKAWAKA\SyliusSecurityPlugin\Security\AdminRoleChoicesProviderInterface;
use AKAWAKA\SyliusSecurityPlugin\Security\AdminRouteAccessCheckerInterface;
use Sylius\Bundle\CoreBundle\Form\Type\User\AdminUserType;
use Sylius\Component\Core\Model\AdminUserInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Adds the admin context roles selector. Roles not managed by this field
 * (ROLE_ADMINISTRATION_ACCESS, ROLE_API_ACCESS, super admin for non super admins...) are preserved.
 */
final class AdminUserTypeExtension extends AbstractTypeExtension
{
    public function __construct(
        private readonly AdminRoleChoicesProviderInterface $roleChoicesProvider,
        private readonly AdminRouteAccessCheckerInterface $routeAccessChecker,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $grantor = $this->tokenStorage->getToken()?->getUser();

        // Administrators editing their own account without managing administrators cannot change roles
        if (null === $grantor || !$this->routeAccessChecker->isGranted($grantor, 'sylius_admin_admin_user_update', false)) {
            return;
        }

        $managedRoles = $this->roleChoicesProvider->getRoles($grantor);

        $builder
            ->add('securityRoles', ChoiceType::class, [
                'label' => 'sylius.ui.roles',
                'choices' => $this->roleChoicesProvider->getChoices($grantor),
                'multiple' => true,
                'expanded' => true,
                'required' => false,
                'mapped' => false,
                'choice_translation_domain' => 'messages',
            ])
            ->addEventListener(FormEvents::POST_SET_DATA, static function (FormEvent $event) use ($managedRoles): void {
                $adminUser = $event->getData();
                if (!$adminUser instanceof AdminUserInterface) {
                    return;
                }

                $event->getForm()->get('securityRoles')->setData(array_values(array_filter(
                    $managedRoles,
                    static fn (string $role): bool => $adminUser->hasRole($role),
                )));
            })
            ->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event) use ($managedRoles): void {
                $adminUser = $event->getData();
                if (!$adminUser instanceof AdminUserInterface) {
                    return;
                }

                /** @var list<string> $selectedRoles */
                $selectedRoles = $event->getForm()->get('securityRoles')->getData() ?? [];

                foreach ($managedRoles as $role) {
                    $adminUser->removeRole($role);
                }

                foreach ($selectedRoles as $role) {
                    $adminUser->addRole($role);
                }
            })
        ;
    }

    public static function getExtendedTypes(): iterable
    {
        return [AdminUserType::class];
    }
}
