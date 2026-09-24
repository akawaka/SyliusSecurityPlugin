<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\EventListener;

use AKAWAKA\SyliusSecurityPlugin\Security\AdminRouteAccessCheckerInterface;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\Model\AdminUserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * After updating their own account, administrators are redirected to the administrators list by default:
 * send them back to their account form when they cannot browse that list.
 */
final class OwnAccountUpdateRedirectListener
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly AdminRouteAccessCheckerInterface $routeAccessChecker,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function __invoke(ResourceControllerEvent $event): void
    {
        $adminUser = $event->getSubject();
        $user = $this->tokenStorage->getToken()?->getUser();
        if (
            !$adminUser instanceof AdminUserInterface ||
            !$user instanceof AdminUserInterface ||
            $adminUser->getId() !== $user->getId() ||
            $this->routeAccessChecker->isGranted($user, 'sylius_admin_admin_user_index', true)
        ) {
            return;
        }

        $event->setResponse(new RedirectResponse(
            $this->urlGenerator->generate('sylius_admin_admin_user_update', ['id' => $adminUser->getId()]),
        ));
    }
}
