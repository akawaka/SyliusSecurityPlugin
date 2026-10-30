<?php

declare(strict_types=1);

namespace Tests\AKAWAKA\SyliusSecurityPlugin\Unit\Menu;

use AKAWAKA\SyliusSecurityPlugin\Menu\AdminActionLogMenuListener;
use Knp\Menu\MenuFactory;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminActionLogMenuListenerTest extends TestCase
{
    public function test_it_adds_the_administrator_actions_item_to_the_configuration_section(): void
    {
        $factory = new MenuFactory();
        $menu = $factory->createItem('root');
        $menu->addChild('configuration');

        (new AdminActionLogMenuListener())(new MenuBuilderEvent($factory, $menu));

        $item = $menu->getChild('configuration')?->getChild('akawaka_admin_action_logs');
        self::assertNotNull($item);
        self::assertSame('akawaka_sylius_security.ui.admin_action_logs', $item->getLabel());
        self::assertSame('tabler:history', $item->getLabelAttribute('icon'));
        self::assertSame(
            [['route' => 'akawaka_sylius_security_admin_admin_action_log_show']],
            $item->getExtra('routes'),
        );
    }

    public function test_it_does_nothing_without_configuration_section(): void
    {
        $factory = new MenuFactory();
        $menu = $factory->createItem('root');
        $menu->addChild('catalog');

        (new AdminActionLogMenuListener())(new MenuBuilderEvent($factory, $menu));

        self::assertSame(['catalog'], array_keys($menu->getChildren()));
        self::assertSame([], $menu->getChild('catalog')?->getChildren());
    }
}
