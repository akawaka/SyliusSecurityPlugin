<?php

declare(strict_types=1);

namespace Tests\AKAWAKA\SyliusSecurityPlugin\Unit\EventListener;

use AKAWAKA\SyliusSecurityPlugin\EventListener\OwnAccountUpdateRedirectListener;
use AKAWAKA\SyliusSecurityPlugin\Security\AdminRouteAccessCheckerInterface;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\Model\AdminUserInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Tests\AKAWAKA\SyliusSecurityPlugin\Unit\AdminUserTrait;

final class OwnAccountUpdateRedirectListenerTest extends TestCase
{
    use AdminUserTrait;

    public function test_it_redirects_to_their_own_account_administrators_who_cannot_list_administrators(): void
    {
        $user = $this->createAdminUser(3);
        $event = new ResourceControllerEvent($this->createAdminUser(3));

        $this->createListener($user, false)($event);

        $response = $event->getResponse();
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/admin/users/3/edit', $response->getTargetUrl());
    }

    public function test_it_keeps_the_default_redirection_for_administrators_who_can_list_administrators(): void
    {
        $user = $this->createAdminUser(3);
        $event = new ResourceControllerEvent($this->createAdminUser(3));

        $this->createListener($user, true)($event);

        self::assertNull($event->getResponse());
    }

    public function test_it_keeps_the_default_redirection_when_updating_another_administrator(): void
    {
        $event = new ResourceControllerEvent($this->createAdminUser(4));

        $this->createListener($this->createAdminUser(3), false)($event);

        self::assertNull($event->getResponse());
    }

    public function test_it_ignores_anonymous_users(): void
    {
        $event = new ResourceControllerEvent($this->createAdminUser(3));

        $this->createListener(null, false)($event);

        self::assertNull($event->getResponse());
    }

    private function createListener(?AdminUserInterface $user, bool $canListAdministrators): OwnAccountUpdateRedirectListener
    {
        $routeAccessChecker = $this->createMock(AdminRouteAccessCheckerInterface::class);
        $routeAccessChecker->method('isGranted')->with($user, 'sylius_admin_admin_user_index', true)->willReturn($canListAdministrators);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->with('sylius_admin_admin_user_update', ['id' => 3])->willReturn('/admin/users/3/edit');

        return new OwnAccountUpdateRedirectListener($this->createTokenStorage($user), $routeAccessChecker, $urlGenerator);
    }
}
