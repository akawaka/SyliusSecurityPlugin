<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

/**
 * Removes the "Administration > Roles" main menu entry, a Sylius Plus placeholder
 * superseded by the roles managed on the administrator form.
 */
final class SyliusPlusRolesPlaceholderMenuListener
{
    private const SECTION = 'sylius.ui.administration';

    private const ITEM = 'roles';

    public function __invoke(MenuBuilderEvent $event): void
    {
        $section = $event->getMenu()->getChild(self::SECTION);
        if (null === $section || true !== $section->getChild(self::ITEM)?->getExtra('plus_logo')) {
            return;
        }

        $section->removeChild(self::ITEM);

        if ([] === $section->getChildren()) {
            $event->getMenu()->removeChild($section);
        }
    }
}
