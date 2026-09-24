<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\Security;

final class AdminContextRegistry implements AdminContextRegistryInterface
{
    /** @var array<string, AdminContext> */
    private array $contexts = [];

    /**
     * [route prefix, context code or null for public prefixes], sorted from longest to shortest prefix.
     *
     * @var list<array{0: string, 1: string|null}>
     */
    private array $prefixes = [];

    /**
     * @param list<string> $publicRoutePrefixes
     * @param array<string, array{route_prefixes: list<string>}> $contexts
     */
    public function __construct(array $publicRoutePrefixes, array $contexts)
    {
        foreach ($publicRoutePrefixes as $prefix) {
            $this->prefixes[] = [$prefix, null];
        }

        foreach ($contexts as $code => $context) {
            $this->contexts[$code] = new AdminContext($code, $context['route_prefixes']);

            foreach ($context['route_prefixes'] as $prefix) {
                $this->prefixes[] = [$prefix, $code];
            }
        }

        usort($this->prefixes, static fn (array $a, array $b): int => strlen($b[0]) <=> strlen($a[0]));
    }

    public function all(): array
    {
        return $this->contexts;
    }

    public function isPublicRoute(string $route): bool
    {
        $match = $this->match($route);

        return null !== $match && null === $match[1];
    }

    public function resolveByRoute(string $route): ?AdminContext
    {
        $code = $this->match($route)[1] ?? null;

        return null === $code ? null : $this->contexts[$code];
    }

    /** @return array{0: string, 1: string|null}|null */
    private function match(string $route): ?array
    {
        foreach ($this->prefixes as $prefix) {
            if (str_starts_with($route, $prefix[0])) {
                return $prefix;
            }
        }

        return null;
    }
}
