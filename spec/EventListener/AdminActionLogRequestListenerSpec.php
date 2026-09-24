<?php

declare(strict_types=1);

namespace spec\AKAWAKA\SyliusSecurityPlugin\EventListener;

use AKAWAKA\SyliusSecurityPlugin\AdminActionLog\AdminActionLogFactoryInterface;
use AKAWAKA\SyliusSecurityPlugin\AdminActionLog\AdminActionLogWriterInterface;
use AKAWAKA\SyliusSecurityPlugin\Entity\AdminActionLogInterface;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Component\Core\Model\ShopUserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

final class AdminActionLogRequestListenerSpec extends ObjectBehavior
{
    function let(
        TokenStorageInterface $tokenStorage,
        TokenInterface $token,
        AdminUserInterface $adminUser,
        AdminActionLogFactoryInterface $factory,
        AdminActionLogWriterInterface $writer,
    ): void {
        $tokenStorage->getToken()->willReturn($token);
        $token->getUser()->willReturn($adminUser);

        $this->beConstructedWith($tokenStorage, $factory, $writer, true, ['sylius_admin_partial_']);
    }

    function it_logs_read_requests(
        HttpKernelInterface $kernel,
        AdminUserInterface $adminUser,
        AdminActionLogFactoryInterface $factory,
        AdminActionLogWriterInterface $writer,
        AdminActionLogInterface $log,
    ): void {
        $request = $this->createRequest('GET', 'sylius_admin_product_index');
        $factory->createFromRequest(AdminActionLogInterface::TYPE_READ, $request, $adminUser)->willReturn($log);

        $log->setStatusCode(200)->shouldBeCalled();
        $writer->write($log)->shouldBeCalled();

        $this(new ResponseEvent($kernel->getWrappedObject(), $request, HttpKernelInterface::MAIN_REQUEST, new Response()));
    }

    function it_logs_write_requests_even_on_excluded_routes(
        HttpKernelInterface $kernel,
        AdminUserInterface $adminUser,
        AdminActionLogFactoryInterface $factory,
        AdminActionLogWriterInterface $writer,
        AdminActionLogInterface $log,
    ): void {
        $request = $this->createRequest('POST', 'sylius_admin_partial_product_update');
        $factory->createFromRequest(AdminActionLogInterface::TYPE_WRITE, $request, $adminUser)->willReturn($log);

        $log->setStatusCode(302)->shouldBeCalled();
        $writer->write($log)->shouldBeCalled();

        $this(new ResponseEvent($kernel->getWrappedObject(), $request, HttpKernelInterface::MAIN_REQUEST, new Response('', 302)));
    }

    function it_does_not_log_read_requests_on_excluded_routes(HttpKernelInterface $kernel, AdminActionLogWriterInterface $writer): void
    {
        $writer->write(Argument::any())->shouldNotBeCalled();

        $this(new ResponseEvent($kernel->getWrappedObject(), $this->createRequest('GET', 'sylius_admin_partial_taxon_tree'), HttpKernelInterface::MAIN_REQUEST, new Response()));
    }

    function it_does_not_log_read_requests_when_disabled(
        TokenStorageInterface $tokenStorage,
        HttpKernelInterface $kernel,
        AdminActionLogFactoryInterface $factory,
        AdminActionLogWriterInterface $writer,
    ): void {
        $this->beConstructedWith($tokenStorage, $factory, $writer, false, []);

        $writer->write(Argument::any())->shouldNotBeCalled();

        $this(new ResponseEvent($kernel->getWrappedObject(), $this->createRequest('GET', 'sylius_admin_product_index'), HttpKernelInterface::MAIN_REQUEST, new Response()));
    }

    function it_does_not_log_authentication_routes(HttpKernelInterface $kernel, AdminActionLogWriterInterface $writer): void
    {
        $writer->write(Argument::any())->shouldNotBeCalled();

        $this(new ResponseEvent($kernel->getWrappedObject(), $this->createRequest('POST', 'sylius_admin_login_check'), HttpKernelInterface::MAIN_REQUEST, new Response()));
    }

    function it_does_not_log_sub_requests(HttpKernelInterface $kernel, AdminActionLogWriterInterface $writer): void
    {
        $writer->write(Argument::any())->shouldNotBeCalled();

        $this(new ResponseEvent($kernel->getWrappedObject(), $this->createRequest('POST', 'sylius_admin_product_update'), HttpKernelInterface::SUB_REQUEST, new Response()));
    }

    function it_does_not_log_requests_of_non_administrators(
        TokenInterface $token,
        ShopUserInterface $shopUser,
        HttpKernelInterface $kernel,
        AdminActionLogWriterInterface $writer,
    ): void {
        $token->getUser()->willReturn($shopUser);

        $writer->write(Argument::any())->shouldNotBeCalled();

        $this(new ResponseEvent($kernel->getWrappedObject(), $this->createRequest('POST', 'sylius_shop_cart_save'), HttpKernelInterface::MAIN_REQUEST, new Response()));
    }

    private function createRequest(string $method, string $route): Request
    {
        $request = Request::create('/admin', $method);
        $request->attributes->set('_route', $route);

        return $request;
    }
}
