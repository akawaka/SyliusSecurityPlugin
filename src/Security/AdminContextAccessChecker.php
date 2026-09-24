<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\Security;

use Symfony\Component\Security\Core\User\UserInterface;

final class AdminContextAccessChecker implements AdminContextAccessCheckerInterface
{
    public function __construct(private readonly string $superAdminRole)
    {
    }

    public function isSuperAdmin(UserInterface $user): bool
    {
        return in_array($this->superAdminRole, $user->getRoles(), true);
    }

    public function canRead(UserInterface $user, AdminContext $context): bool
    {
        return $this->canWrite($user, $context) || in_array($context->getReadRole(), $user->getRoles(), true);
    }

    public function canWrite(UserInterface $user, AdminContext $context): bool
    {
        return $this->isSuperAdmin($user) || in_array($context->getWriteRole(), $user->getRoles(), true);
    }
}
