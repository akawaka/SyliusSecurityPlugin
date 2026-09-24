<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\Entity;

use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Resource\Model\ResourceInterface;

interface AdminActionLogInterface extends ResourceInterface
{
    public const TYPE_READ = 'read';

    public const TYPE_WRITE = 'write';

    public const TYPE_LOGIN = 'login';

    public const TYPE_LOGIN_FAILURE = 'login_failure';

    public const TYPE_LOGOUT = 'logout';

    public const TYPES = [
        self::TYPE_READ,
        self::TYPE_WRITE,
        self::TYPE_LOGIN,
        self::TYPE_LOGIN_FAILURE,
        self::TYPE_LOGOUT,
    ];

    public function getCreatedAt(): \DateTimeImmutable;

    public function setCreatedAt(\DateTimeImmutable $createdAt): void;

    public function getType(): string;

    public function setType(string $type): void;

    public function getAdminUser(): ?AdminUserInterface;

    public function setAdminUser(?AdminUserInterface $adminUser): void;

    public function getAdminEmail(): ?string;

    public function setAdminEmail(?string $adminEmail): void;

    public function getRoute(): ?string;

    public function setRoute(?string $route): void;

    public function getContext(): ?string;

    public function setContext(?string $context): void;

    public function getMethod(): string;

    public function setMethod(string $method): void;

    public function getUri(): string;

    public function setUri(string $uri): void;

    /** @return array<string, mixed> */
    public function getRouteParameters(): array;

    /** @param array<string, mixed> $routeParameters */
    public function setRouteParameters(array $routeParameters): void;

    public function getStatusCode(): ?int;

    public function setStatusCode(?int $statusCode): void;

    public function getIp(): ?string;

    public function setIp(?string $ip): void;

    public function getUserAgent(): ?string;

    public function setUserAgent(?string $userAgent): void;
}
