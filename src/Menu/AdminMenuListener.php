<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\Menu;

use AKAWAKA\SyliusSecurityPlugin\Security\AdminRouteAccessCheckerInterface;
use Knp\Menu\ItemInterface;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Sylius\Component\Core\Model\AdminUserInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Removes the main menu entries pointing to routes the current administrator cannot access,
 * then the sections left empty.
 */
final class AdminMenuListener
{
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly AdminRouteAccessCheckerInterface $routeAccessChecker,
        private readonly string $readRoutePattern,
    ) {
    }

    public function __invoke(MenuBuilderEvent $event): void
    {
        $user = $this->tokenStorage->getToken()?->getUser();
        if (!$user instanceof AdminUserInterface) {
            return;
        }

        foreach ($event->getMenu()->getChildren() as $section) {
            if (!$section->hasChildren()) {
                continue;
            }

            foreach ($section->getChildren() as $item) {
                $route = $this->getRoute($item);
                if (null === $route) {
                    continue;
                }

                $readOnly = 1 === preg_match($this->readRoutePattern, $route);
                if (!$this->routeAccessChecker->isGranted($user, $route, $readOnly)) {
                    $section->removeChild($item);
                }
            }

            if ([] === $section->getChildren()) {
                $event->getMenu()->removeChild($section);
            }
        }
    }

    private function getRoute(ItemInterface $item): ?string
    {
        // The item's own route is appended last by the KnpMenu routing extension
        $routes = $item->getExtra('routes', []);
        $route = is_array($routes) && [] !== $routes ? (end($routes)['route'] ?? null) : null;

        return is_string($route) ? $route : null;
    }
}
