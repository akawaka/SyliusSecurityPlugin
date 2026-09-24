<?php

declare(strict_types=1);

namespace spec\AKAWAKA\SyliusSecurityPlugin\AdminActionLog;

use AKAWAKA\SyliusSecurityPlugin\Entity\AdminActionLog;
use AKAWAKA\SyliusSecurityPlugin\Entity\AdminActionLogInterface;
use AKAWAKA\SyliusSecurityPlugin\Security\AdminContextRegistry;
use PhpSpec\ObjectBehavior;
use Psr\Clock\ClockInterface;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\HttpFoundation\Request;

final class AdminActionLogFactorySpec extends ObjectBehavior
{
    function let(FactoryInterface $decoratedFactory, ClockInterface $clock): void
    {
        $decoratedFactory->createNew()->willReturn(new AdminActionLog());
        $clock->now()->willReturn(new \DateTimeImmutable('2026-09-24 12:00:00'));

        $this->beConstructedWith(
            $decoratedFactory,
            new AdminContextRegistry([], ['catalog' => ['route_prefixes' => ['sylius_admin_product']]]),
            $clock,
        );
    }

    function it_creates_a_log_from_a_request(AdminUserInterface $adminUser): void
    {
        $adminUser->getEmail()->willReturn('admin@example.com');

        $request = Request::create('/admin/products/12/edit?foo=bar', 'PUT', [], [], [], [
            'REMOTE_ADDR' => '10.0.0.1',
            'HTTP_USER_AGENT' => 'Firefox',
        ]);
        $request->attributes->set('_route', 'sylius_admin_product_update');
        $request->attributes->set('_route_params', ['id' => '12', '_sylius' => ['section' => 'admin']]);

        $log = $this->createFromRequest(AdminActionLogInterface::TYPE_WRITE, $request, $adminUser);

        $log->getCreatedAt()->shouldBeLike(new \DateTimeImmutable('2026-09-24 12:00:00'));
        $log->getType()->shouldReturn(AdminActionLogInterface::TYPE_WRITE);
        $log->getAdminUser()->shouldReturn($adminUser);
        $log->getAdminEmail()->shouldReturn('admin@example.com');
        $log->getMethod()->shouldReturn('PUT');
        $log->getUri()->shouldReturn('/admin/products/12/edit?foo=bar');
        $log->getRoute()->shouldReturn('sylius_admin_product_update');
        $log->getContext()->shouldReturn('catalog');
        $log->getRouteParameters()->shouldReturn(['id' => '12']);
        $log->getIp()->shouldReturn('10.0.0.1');
        $log->getUserAgent()->shouldReturn('Firefox');
    }

    function it_creates_a_log_without_administrator_for_failed_logins(): void
    {
        $request = Request::create('/admin/login-check', 'POST');
        $request->attributes->set('_route', 'sylius_admin_login_check');

        $log = $this->createFromRequest(AdminActionLogInterface::TYPE_LOGIN_FAILURE, $request, null, 'unknown@example.com');

        $log->getAdminUser()->shouldReturn(null);
        $log->getAdminEmail()->shouldReturn('unknown@example.com');
        $log->getContext()->shouldReturn(null);
    }
}
