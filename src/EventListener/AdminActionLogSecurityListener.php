<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\EventListener;

use AKAWAKA\SyliusSecurityPlugin\AdminActionLog\AdminActionLogFactoryInterface;
use AKAWAKA\SyliusSecurityPlugin\AdminActionLog\AdminActionLogWriterInterface;
use AKAWAKA\SyliusSecurityPlugin\Entity\AdminActionLogInterface;
use Sylius\Component\Core\Model\AdminUserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/** Logs the administrators logins, failed logins and logouts. */
final class AdminActionLogSecurityListener
{
    /** @param list<string> $firewalls Firewalls whose failed logins are logged */
    public function __construct(
        private readonly AdminActionLogFactoryInterface $adminActionLogFactory,
        private readonly AdminActionLogWriterInterface $adminActionLogWriter,
        private readonly array $firewalls,
    ) {
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        $authenticator = $event->getAuthenticator();
        // Stateless authenticators (JWT) authenticate every API request, only actual logins are logged
        if (!$user instanceof AdminUserInterface || !$authenticator instanceof InteractiveAuthenticatorInterface || !$authenticator->isInteractive()) {
            return;
        }

        $this->log(AdminActionLogInterface::TYPE_LOGIN, $event->getRequest(), $event->getResponse(), $user);
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        if (!in_array($event->getFirewallName(), $this->firewalls, true)) {
            return;
        }

        $badge = $event->getPassport()?->getBadge(UserBadge::class);
        $identifier = $badge instanceof UserBadge ? $badge->getUserIdentifier() : null;

        $this->log(AdminActionLogInterface::TYPE_LOGIN_FAILURE, $event->getRequest(), $event->getResponse(), null, $identifier);
    }

    public function onLogout(LogoutEvent $event): void
    {
        $user = $event->getToken()?->getUser();
        if (!$user instanceof AdminUserInterface) {
            return;
        }

        $this->log(AdminActionLogInterface::TYPE_LOGOUT, $event->getRequest(), $event->getResponse(), $user);
    }

    private function log(string $type, Request $request, ?Response $response, ?AdminUserInterface $user, ?string $identifier = null): void
    {
        $log = $this->adminActionLogFactory->createFromRequest($type, $request, $user, $identifier);
        $log->setStatusCode($response?->getStatusCode());

        $this->adminActionLogWriter->write($log);
    }
}
