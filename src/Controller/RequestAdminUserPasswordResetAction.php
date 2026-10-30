<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\Controller;

use Sylius\Bundle\CoreBundle\Command\Admin\Account\RequestResetPasswordEmail;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\User\Repository\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Sends the "forgotten password" email to another administrator, who cannot have their password changed directly.
 */
final class RequestAdminUserPasswordResetAction
{
    public const CSRF_TOKEN_ID_PREFIX = 'akawaka_request_admin_user_password_reset_';

    /** @param UserRepositoryInterface<AdminUserInterface> $adminUserRepository */
    public function __construct(
        private readonly UserRepositoryInterface $adminUserRepository,
        private readonly MessageBusInterface $commandBus,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
        private readonly RouterInterface $router,
    ) {
    }

    public function __invoke(Request $request, int $id): RedirectResponse
    {
        $adminUser = $this->adminUserRepository->find($id);
        if (!$adminUser instanceof AdminUserInterface) {
            throw new NotFoundHttpException(sprintf('Administrator "%d" not found.', $id));
        }

        $token = new CsrfToken(self::CSRF_TOKEN_ID_PREFIX . $id, (string) $request->request->get('_csrf_token'));
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }

        $this->commandBus->dispatch(new RequestResetPasswordEmail((string) $adminUser->getEmail()));

        $session = $request->getSession();
        if ($session instanceof FlashBagAwareSessionInterface) {
            $session->getFlashBag()->add('success', 'akawaka_sylius_security.admin_user.password_reset_requested');
        }

        return new RedirectResponse($this->router->generate('sylius_admin_admin_user_update', ['id' => $id]));
    }
}
