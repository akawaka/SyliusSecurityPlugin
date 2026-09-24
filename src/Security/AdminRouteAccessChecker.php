<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\Security;

use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Public routes and routes outside the Sylius admin are always granted.
 * Other Sylius admin routes require the context role, or super admin when no context matches.
 */
final class AdminRouteAccessChecker implements AdminRouteAccessCheckerInterface
{
    private const SYLIUS_ADMIN_ROUTE_PREFIX = 'sylius_admin_';

    public function __construct(
        private readonly AdminContextRegistryInterface $contextRegistry,
        private readonly AdminContextAccessCheckerInterface $contextAccessChecker,
    ) {
    }

    public function isGranted(UserInterface $user, string $route, bool $readOnly): bool
    {
        if ($this->contextRegistry->isPublicRoute($route) || $this->contextAccessChecker->isSuperAdmin($user)) {
            return true;
        }

        $context = $this->contextRegistry->resolveByRoute($route);
        if (null === $context) {
            return !str_starts_with($route, self::SYLIUS_ADMIN_ROUTE_PREFIX);
        }

        return $readOnly
            ? $this->contextAccessChecker->canRead($user, $context)
            : $this->contextAccessChecker->canWrite($user, $context);
    }
}
