<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\AdminActionLog;

use AKAWAKA\SyliusSecurityPlugin\Entity\AdminActionLogInterface;
use AKAWAKA\SyliusSecurityPlugin\Security\AdminContextRegistryInterface;
use Psr\Clock\ClockInterface;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\HttpFoundation\Request;

final class AdminActionLogFactory implements AdminActionLogFactoryInterface
{
    private const USER_AGENT_MAX_LENGTH = 512;

    /** @param FactoryInterface<AdminActionLogInterface> $decoratedFactory */
    public function __construct(
        private readonly FactoryInterface $decoratedFactory,
        private readonly AdminContextRegistryInterface $contextRegistry,
        private readonly ClockInterface $clock,
    ) {
    }

    public function createNew(): AdminActionLogInterface
    {
        $log = $this->decoratedFactory->createNew();
        $log->setCreatedAt($this->clock->now());

        return $log;
    }

    public function createFromRequest(
        string $type,
        Request $request,
        ?AdminUserInterface $adminUser,
        ?string $adminEmail = null,
    ): AdminActionLogInterface {
        $log = $this->createNew();
        $log->setType($type);
        $log->setAdminUser($adminUser);
        $log->setAdminEmail($adminEmail ?? $adminUser?->getEmail());
        $log->setMethod($request->getMethod());
        $log->setUri($request->getRequestUri());
        $log->setIp($request->getClientIp());

        $userAgent = $request->headers->get('User-Agent');
        $log->setUserAgent(null === $userAgent ? null : mb_substr($userAgent, 0, self::USER_AGENT_MAX_LENGTH));

        $route = $request->attributes->get('_route');
        if (is_string($route)) {
            $log->setRoute($route);
            $log->setContext($this->contextRegistry->resolveByRoute($route)?->getCode());
        }

        $routeParameters = $request->attributes->get('_route_params');
        if (is_array($routeParameters)) {
            $log->setRouteParameters(array_filter(
                $routeParameters,
                static fn (mixed $key): bool => !str_starts_with((string) $key, '_'),
                \ARRAY_FILTER_USE_KEY,
            ));
        }

        return $log;
    }
}
