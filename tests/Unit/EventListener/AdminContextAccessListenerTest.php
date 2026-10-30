<?php

declare(strict_types=1);

namespace Tests\AKAWAKA\SyliusSecurityPlugin\Unit\EventListener;

use AKAWAKA\SyliusSecurityPlugin\EventListener\AdminContextAccessListener;
use AKAWAKA\SyliusSecurityPlugin\Security\AdminContextAccessChecker;
use AKAWAKA\SyliusSecurityPlugin\Security\AdminContextRegistry;
use AKAWAKA\SyliusSecurityPlugin\Security\AdminRouteAccessChecker;
use AKAWAKA\SyliusSecurityPlugin\Security\AdminRouteAccessCheckerInterface;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Core\Model\AdminUserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Tests\AKAWAKA\SyliusSecurityPlugin\Unit\AdminUserTrait;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class AdminContextAccessListenerTest extends TestCase
{
    use AdminUserTrait;

    private const READ_ROUTE_PATTERN = '/_(index|show|history|statistics)$/';

    public function test_it_lets_granted_routes_through(): void
    {
        $event = $this->createEvent($this->createRequest('sylius_admin_product_index'));

        $this->createListener($this->createAdminUser(1, ['ROLE_ADMIN_CATALOG_READ']))($event);

        self::assertNull($event->getResponse());
    }

    public function test_it_renders_an_access_denied_page_for_a_denied_route(): void
    {
        $event = $this->createEvent($this->createRequest('sylius_admin_order_index'));

        $this->createListener($this->createAdminUser(1, ['ROLE_ADMIN_CATALOG_WRITE']))($event);

        $response = $event->getResponse();
        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('context=sales can_read=no', $response->getContent());
    }

    public function test_it_tells_whether_the_denied_page_can_be_read(): void
    {
        $event = $this->createEvent($this->createRequest('sylius_admin_product_update'));

        $this->createListener($this->createAdminUser(1, ['ROLE_ADMIN_CATALOG_READ']))($event);

        self::assertSame('context=catalog can_read=yes', $event->getResponse()?->getContent());
    }

    public function test_it_requires_the_write_role_for_unsafe_requests_on_read_routes(): void
    {
        $event = $this->createEvent($this->createRequest('sylius_admin_product_index', Request::METHOD_POST));

        $this->createListener($this->createAdminUser(1, ['ROLE_ADMIN_CATALOG_READ']))($event);

        self::assertSame(Response::HTTP_FORBIDDEN, $event->getResponse()?->getStatusCode());
    }

    public function test_it_throws_an_access_denied_exception_for_ajax_requests(): void
    {
        $request = $this->createRequest('sylius_admin_order_index');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $this->expectException(AccessDeniedException::class);

        $this->createListener($this->createAdminUser(1))($this->createEvent($request));
    }

    public function test_it_throws_an_access_denied_exception_for_non_html_requests(): void
    {
        $request = $this->createRequest('sylius_admin_order_index');
        $request->headers->set('Accept', 'application/json');

        $this->expectException(AccessDeniedException::class);

        $this->createListener($this->createAdminUser(1))($this->createEvent($request));
    }

    public function test_it_lets_administrators_update_their_own_account(): void
    {
        $event = $this->createEvent($this->createRequest('sylius_admin_admin_user_update', Request::METHOD_PUT, ['id' => '7']));

        $this->createListener($this->createAdminUser(7))($event);

        self::assertNull($event->getResponse());
    }

    public function test_it_denies_updating_another_administrator_account(): void
    {
        $event = $this->createEvent($this->createRequest('sylius_admin_admin_user_update', Request::METHOD_PUT, ['id' => '8']));

        $this->createListener($this->createAdminUser(7))($event);

        self::assertSame(Response::HTTP_FORBIDDEN, $event->getResponse()?->getStatusCode());
    }

    public function test_it_ignores_sub_requests_anonymous_users_and_requests_without_route(): void
    {
        $routeAccessChecker = $this->createMock(AdminRouteAccessCheckerInterface::class);
        $routeAccessChecker->expects(self::never())->method('isGranted');

        $subRequestEvent = $this->createEvent($this->createRequest('sylius_admin_order_index'), HttpKernelInterface::SUB_REQUEST);
        $this->createListener($this->createAdminUser(1), $routeAccessChecker)($subRequestEvent);

        $anonymousEvent = $this->createEvent($this->createRequest('sylius_admin_order_index'));
        $this->createListener(null, $routeAccessChecker)($anonymousEvent);

        $noRouteEvent = $this->createEvent(new Request());
        $this->createListener($this->createAdminUser(1), $routeAccessChecker)($noRouteEvent);

        self::assertNull($subRequestEvent->getResponse());
        self::assertNull($anonymousEvent->getResponse());
        self::assertNull($noRouteEvent->getResponse());
    }

    private function createListener(
        ?AdminUserInterface $user,
        ?AdminRouteAccessCheckerInterface $routeAccessChecker = null,
    ): AdminContextAccessListener {
        $contextRegistry = new AdminContextRegistry(['sylius_admin_dashboard'], [
            'catalog' => ['route_prefixes' => ['sylius_admin_product']],
            'sales' => ['route_prefixes' => ['sylius_admin_order']],
            'administration' => ['route_prefixes' => ['sylius_admin_admin_user']],
        ]);

        return new AdminContextAccessListener(
            $this->createTokenStorage($user),
            $routeAccessChecker ?? new AdminRouteAccessChecker($contextRegistry, new AdminContextAccessChecker('ROLE_SUPER_ADMIN')),
            $contextRegistry,
            new Environment(new ArrayLoader([
                '@AkawakaSyliusSecurityPlugin/admin/access_denied.html.twig' => 'context={{ context.code }} can_read={{ can_read ? "yes" : "no" }}',
            ])),
            self::READ_ROUTE_PATTERN,
        );
    }

    /** @param array<string, string> $attributes */
    private function createRequest(string $route, string $method = Request::METHOD_GET, array $attributes = []): Request
    {
        $request = Request::create('/admin', $method);
        $request->attributes->add(['_route' => $route, ...$attributes]);

        return $request;
    }

    private function createEvent(Request $request, int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent($this->createMock(HttpKernelInterface::class), $request, $requestType);
    }
}
