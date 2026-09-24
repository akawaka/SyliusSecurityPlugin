<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\Security;

use Symfony\Component\Security\Core\User\UserInterface;

final class AdminRoleChoicesProvider implements AdminRoleChoicesProviderInterface
{
    public function __construct(
        private readonly AdminContextRegistryInterface $contextRegistry,
        private readonly AdminContextAccessCheckerInterface $contextAccessChecker,
        private readonly string $superAdminRole,
    ) {
    }

    public function getChoices(?UserInterface $grantor): array
    {
        $choices = [];

        // Only a super admin can grant super admin, to prevent privilege escalation
        if (null !== $grantor && $this->contextAccessChecker->isSuperAdmin($grantor)) {
            $choices['akawaka_sylius_security.ui.role.super_admin'] = $this->superAdminRole;
        }

        foreach ($this->contextRegistry->all() as $code => $context) {
            $choices['akawaka_sylius_security.ui.context.' . $code] = [
                'akawaka_sylius_security.ui.role.read' => $context->getReadRole(),
                'akawaka_sylius_security.ui.role.write' => $context->getWriteRole(),
            ];
        }

        return $choices;
    }

    public function getRoles(?UserInterface $grantor): array
    {
        $roles = [];
        foreach ($this->getChoices($grantor) as $choice) {
            array_push($roles, ...array_values((array) $choice));
        }

        return $roles;
    }
}
