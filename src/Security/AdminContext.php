<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\Security;

final class AdminContext
{
    private const ROLE_PREFIX = 'ROLE_ADMIN_';

    /** @param list<string> $routePrefixes */
    public function __construct(
        private readonly string $code,
        private readonly array $routePrefixes,
    ) {
    }

    public function getCode(): string
    {
        return $this->code;
    }

    /** @return list<string> */
    public function getRoutePrefixes(): array
    {
        return $this->routePrefixes;
    }

    public function getReadRole(): string
    {
        return self::ROLE_PREFIX . strtoupper($this->code) . '_READ';
    }

    public function getWriteRole(): string
    {
        return self::ROLE_PREFIX . strtoupper($this->code) . '_WRITE';
    }
}
