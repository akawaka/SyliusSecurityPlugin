<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\EventListener;

use AKAWAKA\SyliusSecurityPlugin\AdminActionLog\AdminActionLogFactoryInterface;
use AKAWAKA\SyliusSecurityPlugin\AdminActionLog\AdminActionLogWriterInterface;
use AKAWAKA\SyliusSecurityPlugin\Entity\AdminActionLogInterface;
use Sylius\Component\Core\Model\AdminUserInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Logs every request made by an administrator. Read requests on routes matching an excluded prefix are skipped,
 * write requests are always logged.
 */
final class AdminActionLogRequestListener
{
    /** Logged by AdminActionLogSecurityListener */
    private const AUTHENTICATION_ROUTES = [
        'sylius_admin_login_check',
        'sylius_admin_logout',
        'sylius_api_admin_authentication_token',
    ];

    /** @param list<string> $excludedRoutePrefixes */
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly AdminActionLogFactoryInterface $adminActionLogFactory,
        private readonly AdminActionLogWriterInterface $adminActionLogWriter,
        private readonly bool $logReadActions,
        private readonly array $excludedRoutePrefixes,
    ) {
    }

    public function __invoke(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        $user = $this->tokenStorage->getToken()?->getUser();
        if (!is_string($route) || !$user instanceof AdminUserInterface || in_array($route, self::AUTHENTICATION_ROUTES, true)) {
            return;
        }

        $read = $request->isMethodSafe();
        if ($read && (!$this->logReadActions || $this->isExcluded($route))) {
            return;
        }

        $log = $this->adminActionLogFactory->createFromRequest(
            $read ? AdminActionLogInterface::TYPE_READ : AdminActionLogInterface::TYPE_WRITE,
            $request,
            $user,
        );
        $log->setStatusCode($event->getResponse()->getStatusCode());

        $this->adminActionLogWriter->write($log);
    }

    private function isExcluded(string $route): bool
    {
        foreach ($this->excludedRoutePrefixes as $prefix) {
            if (str_starts_with($route, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
