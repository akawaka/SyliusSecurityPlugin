<?php

declare(strict_types=1);

namespace Tests\AKAWAKA\SyliusSecurityPlugin\Unit\DependencyInjection;

use AKAWAKA\SyliusSecurityPlugin\DependencyInjection\AkawakaSyliusSecurityExtension;
use AKAWAKA\SyliusSecurityPlugin\DependencyInjection\Configuration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AkawakaSyliusSecurityExtensionTest extends TestCase
{
    public function test_it_loads_the_default_configuration(): void
    {
        $container = $this->load([]);

        self::assertSame('ROLE_SUPER_ADMIN', $container->getParameter('akawaka_sylius_security.super_admin_role'));
        self::assertSame(Configuration::DEFAULT_PUBLIC_ROUTE_PREFIXES, $container->getParameter('akawaka_sylius_security.public_route_prefixes'));
        self::assertSame(Configuration::DEFAULT_CONTEXTS, $container->getParameter('akawaka_sylius_security.contexts'));
        self::assertSame([], $container->getParameter('akawaka_sylius_security.roles'));
        self::assertTrue($container->getParameter('akawaka_sylius_security.admin_action_log.log_read_actions'));
        self::assertSame(90, $container->getParameter('akawaka_sylius_security.admin_action_log.retention_days'));
        self::assertTrue($container->hasDefinition('akawaka_sylius_security.admin_context_registry'));
        self::assertTrue($container->hasDefinition('akawaka_sylius_security.listener.admin_action_log_request'));
        self::assertTrue($container->hasDefinition('akawaka_sylius_security.listener.admin_action_log_security'));
    }

    public function test_it_adds_the_configured_route_prefixes_to_the_default_ones(): void
    {
        $container = $this->load([
            'public_route_prefixes' => ['app_admin_help', 'sylius_admin_dashboard'],
            'admin_action_log' => ['excluded_route_prefixes' => ['app_admin_ping', '_wdt']],
        ]);

        self::assertSame(
            [...Configuration::DEFAULT_PUBLIC_ROUTE_PREFIXES, 'app_admin_help'],
            $container->getParameter('akawaka_sylius_security.public_route_prefixes'),
        );
        self::assertSame(
            [...Configuration::DEFAULT_ADMIN_ACTION_LOG_EXCLUDED_ROUTE_PREFIXES, 'app_admin_ping'],
            $container->getParameter('akawaka_sylius_security.admin_action_log.excluded_route_prefixes'),
        );
    }

    public function test_it_merges_the_configured_contexts_with_the_default_ones_by_code(): void
    {
        $container = $this->load(['contexts' => [
            'catalog' => ['route_prefixes' => ['sylius_admin_product']],
            'audit' => ['route_prefixes' => ['app_admin_audit']],
        ]]);

        /** @var array<string, array{route_prefixes: list<string>}> $contexts */
        $contexts = $container->getParameter('akawaka_sylius_security.contexts');

        self::assertSame(['sylius_admin_product'], $contexts['catalog']['route_prefixes']);
        self::assertSame(['app_admin_audit'], $contexts['audit']['route_prefixes']);
        self::assertSame(Configuration::DEFAULT_CONTEXTS['sales'], $contexts['sales']);
    }

    public function test_it_accepts_generic_roles(): void
    {
        $container = $this->load(['roles' => ['ROLE_API_ACCESS' => 'akawaka_sylius_security.ui.role.api_access']]);

        self::assertSame(
            ['ROLE_API_ACCESS' => 'akawaka_sylius_security.ui.role.api_access'],
            $container->getParameter('akawaka_sylius_security.roles'),
        );
    }

    #[DataProvider('provideInvalidRoles')]
    public function test_it_rejects_invalid_or_reserved_roles(string $role): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->load([
            'super_admin_role' => 'ROLE_ROOT',
            'contexts' => ['audit' => ['route_prefixes' => ['app_admin_audit']]],
            'roles' => [$role => 'Label'],
        ]);
    }

    /** @return iterable<string, array{string}> */
    public static function provideInvalidRoles(): iterable
    {
        yield 'without the ROLE_ prefix' => ['API_ACCESS'];
        yield 'admin access role' => ['ROLE_ADMINISTRATION_ACCESS'];
        yield 'super admin role' => ['ROLE_ROOT'];
        yield 'default context read role' => ['ROLE_ADMIN_CATALOG_READ'];
        yield 'configured context write role' => ['ROLE_ADMIN_AUDIT_WRITE'];
    }

    public function test_it_removes_the_admin_action_log_listeners_when_disabled(): void
    {
        $container = $this->load(['admin_action_log' => ['enabled' => false]]);

        self::assertFalse($container->hasDefinition('akawaka_sylius_security.listener.admin_action_log_request'));
        self::assertFalse($container->hasDefinition('akawaka_sylius_security.listener.admin_action_log_security'));
        self::assertTrue($container->hasDefinition('akawaka_sylius_security.command.purge_admin_action_logs'));
    }

    public function test_it_prepends_the_doctrine_mapping(): void
    {
        $container = new ContainerBuilder();

        (new AkawakaSyliusSecurityExtension())->prepend($container);

        self::assertSame([[
            'orm' => [
                'mappings' => [
                    'AkawakaSyliusSecurityPlugin' => [
                        'type' => 'xml',
                        'dir' => 'config/doctrine',
                        'prefix' => 'AKAWAKA\SyliusSecurityPlugin\Entity',
                        'is_bundle' => true,
                    ],
                ],
            ],
        ]], $container->getExtensionConfig('doctrine'));
    }

    /** @param array<string, mixed> $config */
    private function load(array $config): ContainerBuilder
    {
        $container = new ContainerBuilder();

        (new AkawakaSyliusSecurityExtension())->load([$config], $container);

        return $container;
    }
}
