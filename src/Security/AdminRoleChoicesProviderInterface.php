<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\Security;

use Symfony\Component\Security\Core\User\UserInterface;

interface AdminRoleChoicesProviderInterface
{
    /**
     * Roles the given user is allowed to assign, as ChoiceType choices (grouped by context).
     *
     * @return array<string, string|array<string, string>>
     */
    public function getChoices(?UserInterface $grantor): array;

    /** @return list<string> */
    public function getRoles(?UserInterface $grantor): array;
}
