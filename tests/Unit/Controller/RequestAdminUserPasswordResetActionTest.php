<?php

declare(strict_types=1);

namespace Tests\AKAWAKA\SyliusSecurityPlugin\Unit\Controller;

use AKAWAKA\SyliusSecurityPlugin\Controller\RequestAdminUserPasswordResetAction;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\Message\Admin\Account\RequestResetPasswordEmail;
use Sylius\Component\User\Repository\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Tests\AKAWAKA\SyliusSecurityPlugin\Unit\AdminUserTrait;

final class RequestAdminUserPasswordResetActionTest extends TestCase
{
    use AdminUserTrait;

    public function test_it_sends_the_password_reset_email_and_redirects_to_the_administrator_page(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->method('find')->with(5)->willReturn($this->createAdminUser(5, [], 'john@example.com'));

        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfTokenManager
            ->method('isTokenValid')
            ->with(new CsrfToken('akawaka_request_admin_user_password_reset_5', 'token'))
            ->willReturn(true)
        ;

        $message = new RequestResetPasswordEmail('john@example.com');
        $commandBus = $this->createMock(MessageBusInterface::class);
        $commandBus->expects(self::once())->method('dispatch')->with($message)->willReturn(new Envelope($message));

        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->with('sylius_admin_admin_user_update', ['id' => 5])->willReturn('/admin/users/5/edit');

        $session = new Session(new MockArraySessionStorage());
        $request = new Request(request: ['_csrf_token' => 'token']);
        $request->setSession($session);

        $response = (new RequestAdminUserPasswordResetAction($repository, $commandBus, $csrfTokenManager, $router))($request, 5);

        self::assertSame('/admin/users/5/edit', $response->getTargetUrl());
        self::assertSame(
            ['akawaka_sylius_security.admin_user.password_reset_requested'],
            $session->getFlashBag()->get('success'),
        );
    }

    public function test_it_fails_when_the_administrator_does_not_exist(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->method('find')->willReturn(null);

        $this->expectException(NotFoundHttpException::class);

        (new RequestAdminUserPasswordResetAction(
            $repository,
            $this->createMock(MessageBusInterface::class),
            $this->createMock(CsrfTokenManagerInterface::class),
            $this->createMock(RouterInterface::class),
        ))(new Request(), 5);
    }

    public function test_it_fails_when_the_csrf_token_is_invalid(): void
    {
        $repository = $this->createMock(UserRepositoryInterface::class);
        $repository->method('find')->willReturn($this->createAdminUser(5));
        $csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $csrfTokenManager->method('isTokenValid')->willReturn(false);
        $commandBus = $this->createMock(MessageBusInterface::class);
        $commandBus->expects(self::never())->method('dispatch');

        $this->expectException(AccessDeniedHttpException::class);

        (new RequestAdminUserPasswordResetAction(
            $repository,
            $commandBus,
            $csrfTokenManager,
            $this->createMock(RouterInterface::class),
        ))(new Request(request: ['_csrf_token' => 'forged']), 5);
    }
}
