<?php

declare(strict_types=1);

namespace Tests\AKAWAKA\SyliusSecurityPlugin\Unit;

use Sylius\Component\Core\Model\AdminUser;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\User\Model\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

trait AdminUserTrait
{
    /** @param list<string> $roles */
    private function createAdminUser(?int $id, array $roles = [], string $email = 'admin@example.com'): AdminUserInterface
    {
        $adminUser = new AdminUser();
        $adminUser->setEmail($email);
        foreach ($roles as $role) {
            $adminUser->addRole($role);
        }

        (new \ReflectionProperty(User::class, 'id'))->setValue($adminUser, $id);

        return $adminUser;
    }

    private function createTokenStorage(?AdminUserInterface $user): TokenStorageInterface
    {
        $tokenStorage = new TokenStorage();
        if (null !== $user) {
            $tokenStorage->setToken(new UsernamePasswordToken($user, 'admin', $user->getRoles()));
        }

        return $tokenStorage;
    }
}
