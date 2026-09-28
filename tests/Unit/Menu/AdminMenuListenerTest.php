<?php

declare(strict_types=1);

namespace Tests\AKAWAKA\SyliusSecurityPlugin\Unit\Menu;

use AKAWAKA\SyliusSecurityPlugin\Menu\AdminMenuListener;
use AKAWAKA\SyliusSecurityPlugin\Security\AdminContextAccessChecker;
use AKAWAKA\SyliusSecurityPlugin\Security\AdminContextRegistry;
use AKAWAKA\SyliusSecurityPlugin\Security\AdminRouteAccessChecker;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuFactory;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Sylius\Component\Core\Model\AdminUserInterface;
use Tests\AKAWAKA\SyliusSecurityPlugin\Unit\AdminUserTrait;

final class AdminMenuListenerTest extends TestCase
{
    use AdminUserTrait;

    public function test_it_removes_the_items_the_administrator_cannot_access(): void
    {
        $menu = $this->createMenu();

        $this->dispatch($menu, $this->createAdminUser(1, ['ROLE_ADMIN_CATALOG_READ']));

        self::assertSame(['catalog', 'custom'], array_keys($menu->getChildren()));
        self::assertSame(['products', 'taxons'], array_keys($menu->getChild('catalog')?->getChildren() ?? []));
        self::assertSame(['help'], array_keys($menu->getChild('custom')?->getChildren() ?? []));
    }

    public function test_it_checks_the_write_role_for_items_not_matching_the_read_route_pattern(): void
    {
        $menu = $this->createMenu();
        $menu->getChild('catalog')?->addChild('inventory', ['extras' => ['routes' => [['route' => 'sylius_admin_inventory_update']]]]);

        $this->dispatch($menu, $this->createAdminUser(1, ['ROLE_ADMIN_CATALOG_READ']));

        self::assertNull($menu->getChild('catalog')?->getChild('inventory'));
    }

    public function test_it_uses_the_last_route_of_the_item(): void
    {
        $menu = (new MenuFactory())->createItem('root');
        $menu->addChild('sales')->addChild('orders', ['extras' => ['routes' => [
            ['route' => 'sylius_admin_product_index'],
            ['route' => 'sylius_admin_order_index'],
        ]]]);

        $this->dispatch($menu, $this->createAdminUser(1, ['ROLE_ADMIN_CATALOG_READ']));

        self::assertNull($menu->getChild('sales'));
    }

    public function test_it_keeps_the_whole_menu_for_super_admins(): void
    {
        $menu = $this->createMenu();

        $this->dispatch($menu, $this->createAdminUser(1, ['ROLE_SUPER_ADMIN']));

        self::assertSame(['catalog', 'sales', 'custom'], array_keys($menu->getChildren()));
        self::assertCount(2, $menu->getChild('sales')?->getChildren() ?? []);
    }

    public function test_it_leaves_the_menu_untouched_without_authenticated_administrator(): void
    {
        $menu = $this->createMenu();

        $this->dispatch($menu, null);

        self::assertSame(['catalog', 'sales', 'custom'], array_keys($menu->getChildren()));
    }

    private function dispatch(ItemInterface $menu, ?AdminUserInterface $user): void
    {
        $listener = new AdminMenuListener(
            $this->createTokenStorage($user),
            new AdminRouteAccessChecker(
                new AdminContextRegistry([], [
                    'catalog' => ['route_prefixes' => ['sylius_admin_product', 'sylius_admin_taxon', 'sylius_admin_inventory']],
                    'sales' => ['route_prefixes' => ['sylius_admin_order', 'sylius_admin_payment']],
                ]),
                new AdminContextAccessChecker('ROLE_SUPER_ADMIN'),
            ),
            '/_(index|show|history|statistics)$/',
        );

        $listener(new MenuBuilderEvent(new MenuFactory(), $menu));
    }

    private function createMenu(): ItemInterface
    {
        $menu = (new MenuFactory())->createItem('root');

        $catalog = $menu->addChild('catalog');
        $catalog->addChild('products', ['extras' => ['routes' => [['route' => 'sylius_admin_product_index']]]]);
        $catalog->addChild('taxons', ['extras' => ['routes' => [['route' => 'sylius_admin_taxon_index']]]]);

        $sales = $menu->addChild('sales');
        $sales->addChild('orders', ['extras' => ['routes' => [['route' => 'sylius_admin_order_index']]]]);
        $sales->addChild('payments', ['extras' => ['routes' => [['route' => 'sylius_admin_payment_index']]]]);

        // Items without route (e.g. external links) are always kept
        $menu->addChild('custom')->addChild('help', ['uri' => 'https://example.com']);

        return $menu;
    }
}
