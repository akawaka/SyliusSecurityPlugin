<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\Security;

interface AdminContextRegistryInterface
{
    /** @return array<string, AdminContext> */
    public function all(): array;

    public function isPublicRoute(string $route): bool;

    public function resolveByRoute(string $route): ?AdminContext;
}
