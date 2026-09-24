<?php

declare(strict_types=1);

namespace spec\AKAWAKA\SyliusSecurityPlugin\Menu;

use Knp\Menu\MenuFactory;
use PhpSpec\ObjectBehavior;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class SyliusPlusRolesPlaceholderMenuListenerSpec extends ObjectBehavior
{
    function it_removes_the_sylius_plus_roles_placeholder_and_its_empty_section(): void
    {
        $factory = new MenuFactory();
        $menu = $factory->createItem('root');
        $menu->addChild('sylius.ui.administration')->addChild('roles')->setExtra('plus_logo', true);

        $this->__invoke(new MenuBuilderEvent($factory, $menu));

        if (null !== $menu->getChild('sylius.ui.administration')) {
            throw new \RuntimeException('The administration section should have been removed.');
        }
    }

    function it_keeps_the_section_when_other_entries_were_added(): void
    {
        $factory = new MenuFactory();
        $menu = $factory->createItem('root');
        $section = $menu->addChild('sylius.ui.administration');
        $section->addChild('roles')->setExtra('plus_logo', true);
        $section->addChild('audit_log');

        $this->__invoke(new MenuBuilderEvent($factory, $menu));

        if (null !== $section->getChild('roles') || null === $section->getChild('audit_log')) {
            throw new \RuntimeException('Only the Sylius Plus placeholder should have been removed.');
        }
    }

    function it_keeps_a_roles_entry_that_is_not_the_sylius_plus_placeholder(): void
    {
        $factory = new MenuFactory();
        $menu = $factory->createItem('root');
        $menu->addChild('sylius.ui.administration')->addChild('roles');

        $this->__invoke(new MenuBuilderEvent($factory, $menu));

        if (null === $menu->getChild('sylius.ui.administration')?->getChild('roles')) {
            throw new \RuntimeException('A real roles entry should be kept.');
        }
    }
}
