<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\Form\Extension;

use Sylius\Bundle\CoreBundle\Form\Type\User\AdminUserType;
use Sylius\Component\Core\Model\AdminUserInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Administrators can only change their own password: the password field is removed
 * when editing another administrator, who has to use the password reset email instead.
 */
final class AdminUserPasswordTypeExtension extends AbstractTypeExtension
{
    public function __construct(private readonly TokenStorageInterface $tokenStorage)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $adminUser = $event->getData();
            if (!$adminUser instanceof AdminUserInterface || null === $adminUser->getId()) {
                return;
            }

            $currentUser = $this->tokenStorage->getToken()?->getUser();
            if ($currentUser instanceof AdminUserInterface && $currentUser->getId() === $adminUser->getId()) {
                return;
            }

            $event->getForm()->remove('plainPassword');
        });
    }

    public static function getExtendedTypes(): iterable
    {
        return [AdminUserType::class];
    }
}
