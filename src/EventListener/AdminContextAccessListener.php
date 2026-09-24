<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\EventListener;

use AKAWAKA\SyliusSecurityPlugin\Security\AdminContextRegistryInterface;
use AKAWAKA\SyliusSecurityPlugin\Security\AdminRouteAccessCheckerInterface;
use Sylius\Component\Core\Model\AdminUserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Twig\Environment;

final class AdminContextAccessListener
{
    /** Routes an administrator can always use on their own account ("My account" in the admin header) */
    private const OWN_ACCOUNT_ROUTES = ['sylius_admin_admin_user_update', 'sylius_admin_admin_user_remove_avatar'];

    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly AdminRouteAccessCheckerInterface $routeAccessChecker,
        private readonly AdminContextRegistryInterface $contextRegistry,
        private readonly Environment $twig,
        private readonly string $readRoutePattern,
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        $user = $this->tokenStorage->getToken()?->getUser();
        if (!is_string($route) || !$user instanceof AdminUserInterface) {
            return;
        }

        if (in_array($route, self::OWN_ACCOUNT_ROUTES, true) && $this->isOwnAccount($request, $user)) {
            return;
        }

        $readOnly = $request->isMethodSafe() && 1 === preg_match($this->readRoutePattern, $route);

        if ($this->routeAccessChecker->isGranted($user, $route, $readOnly)) {
            return;
        }

        // AJAX calls get a bare 403, pages get a proper access denied page in the admin layout
        if ($request->isXmlHttpRequest() || 'html' !== ($request->getPreferredFormat() ?? 'html')) {
            throw new AccessDeniedException(sprintf('Access to the admin route "%s" is not granted.', $route));
        }

        $event->setResponse(new Response(
            $this->twig->render('@AkawakaSyliusSecurityPlugin/Admin/accessDenied.html.twig', [
                'context' => $this->contextRegistry->resolveByRoute($route),
                'can_read' => !$readOnly && $this->routeAccessChecker->isGranted($user, $route, true),
            ]),
            Response::HTTP_FORBIDDEN,
        ));
    }

    private function isOwnAccount(Request $request, AdminUserInterface $user): bool
    {
        $id = $request->attributes->get('id');

        return is_scalar($id) && (string) $id === (string) $user->getId();
    }
}
