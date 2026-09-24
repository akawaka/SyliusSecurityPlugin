<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public const DEFAULT_PUBLIC_ROUTE_PREFIXES = [
        'sylius_admin_dashboard',
        'sylius_admin_login',
        'sylius_admin_logout',
        'sylius_admin_request_password_reset',
        'sylius_admin_render_reset_password_page',
        'sylius_admin_render_password_reset',
        'sylius_admin_password_reset',
        'sylius_admin_partial_',
        'sylius_admin_ajax_',
        'sylius_admin_get_',
        'sylius_admin_render_attribute_forms',
    ];

    public const DEFAULT_CONTEXTS = [
        'catalog' => [
            'route_prefixes' => [
                'sylius_admin_product',
                'sylius_admin_taxon',
                'sylius_admin_inventory',
                'sylius_admin_ajax_taxon_move',
                'sylius_admin_ajax_product_variants_update_position',
                'sylius_admin_ajax_product_taxons_update_position',
            ],
        ],
        'sales' => [
            'route_prefixes' => [
                'sylius_admin_order',
                'sylius_admin_payment',
                'sylius_admin_shipment',
            ],
        ],
        'customers' => [
            'route_prefixes' => [
                'sylius_admin_customer',
                'sylius_admin_shop_user',
                'sylius_admin_impersonate_user',
            ],
        ],
        'marketing' => [
            'route_prefixes' => [
                'sylius_admin_promotion',
                'sylius_admin_catalog_promotion',
                'sylius_admin_product_review',
            ],
        ],
        'configuration' => [
            'route_prefixes' => [
                'sylius_admin_channel',
                'sylius_admin_country',
                'sylius_admin_province',
                'sylius_admin_zone',
                'sylius_admin_currency',
                'sylius_admin_exchange_rate',
                'sylius_admin_locale',
                'sylius_admin_payment_method',
                'sylius_admin_shipping_method',
                'sylius_admin_shipping_category',
                'sylius_admin_tax_category',
                'sylius_admin_tax_rate',
            ],
        ],
        'administration' => [
            'route_prefixes' => [
                'sylius_admin_admin_user',
            ],
        ],
    ];

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('akawaka_sylius_security');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $children = $rootNode->children();

        $children->scalarNode('super_admin_role')
            ->defaultValue('ROLE_SUPER_ADMIN')
            ->cannotBeEmpty()
        ;

        $children->scalarNode('read_route_pattern')
            ->info('Routes matching this pattern and requested with GET/HEAD only require the READ role.')
            ->defaultValue('/_(index|show|history|statistics)$/')
            ->cannotBeEmpty()
        ;

        $children->arrayNode('public_route_prefixes')
            ->info('Admin route prefixes accessible to any administrator, added to the default ones.')
            ->scalarPrototype()
        ;

        $context = $children->arrayNode('contexts')
            ->info('Admin contexts merged with the default ones by code. The longest matching route prefix (public prefixes included) wins.')
            ->useAttributeAsKey('code')
            ->arrayPrototype()
        ;
        $context->children()->arrayNode('route_prefixes')
            ->requiresAtLeastOneElement()
            ->scalarPrototype()
        ;

        return $treeBuilder;
    }
}
