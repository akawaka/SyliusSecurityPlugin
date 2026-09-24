<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\Security;

use Symfony\Component\Security\Core\User\UserInterface;

interface AdminContextAccessCheckerInterface
{
    public function isSuperAdmin(UserInterface $user): bool;

    public function canRead(UserInterface $user, AdminContext $context): bool;

    public function canWrite(UserInterface $user, AdminContext $context): bool;
}
