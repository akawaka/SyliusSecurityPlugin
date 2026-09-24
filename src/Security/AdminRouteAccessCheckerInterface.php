<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\Security;

use Symfony\Component\Security\Core\User\UserInterface;

interface AdminRouteAccessCheckerInterface
{
    public function isGranted(UserInterface $user, string $route, bool $readOnly): bool;
}
