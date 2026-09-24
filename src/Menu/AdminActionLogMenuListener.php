<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

/** Adds the administrator action logs to the "Configuration" section of the main menu, next to the administrators. */
final class AdminActionLogMenuListener
{
    private const SECTION = 'configuration';

    public function __invoke(MenuBuilderEvent $event): void
    {
        $section = $event->getMenu()->getChild(self::SECTION);
        if (null === $section) {
            return;
        }

        $section
            ->addChild('akawaka_admin_action_logs', [
                'route' => 'akawaka_sylius_security_admin_admin_action_log_index',
                'extras' => ['routes' => [
                    ['route' => 'akawaka_sylius_security_admin_admin_action_log_show'],
                ]],
            ])
            ->setLabel('akawaka_sylius_security.ui.admin_action_logs')
            ->setLabelAttribute('icon', 'history')
        ;
    }
}
