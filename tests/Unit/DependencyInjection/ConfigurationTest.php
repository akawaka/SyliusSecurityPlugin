<?php

declare(strict_types=1);

namespace Tests\AKAWAKA\SyliusSecurityPlugin\Unit\DependencyInjection;

use AKAWAKA\SyliusSecurityPlugin\DependencyInjection\Configuration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    public function test_it_has_default_values(): void
    {
        self::assertSame([
            'super_admin_role' => 'ROLE_SUPER_ADMIN',
            'read_route_pattern' => '/_(index|show|history|statistics)$/',
            'public_route_prefixes' => [],
            'roles' => [],
            'contexts' => [],
            'admin_action_log' => [
                'enabled' => true,
                'log_read_actions' => true,
                'retention_days' => 90,
                'excluded_route_prefixes' => [],
                'firewalls' => ['admin', 'new_api_admin_user'],
            ],
        ], $this->process([]));
    }

    public function test_it_keeps_role_names_and_context_codes_as_configured(): void
    {
        $config = $this->process([
            'roles' => ['ROLE_API-ACCESS' => 'API access'],
            'contexts' => ['audit' => ['route_prefixes' => ['app_admin_audit']]],
        ]);

        self::assertSame(['ROLE_API-ACCESS' => 'API access'], $config['roles']);
        self::assertSame(['audit' => ['route_prefixes' => ['app_admin_audit']]], $config['contexts']);
    }

    /** @param array<string, mixed> $config */
    #[DataProvider('provideInvalidConfigurations')]
    public function test_it_rejects_invalid_configurations(array $config): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process($config);
    }

    /** @return iterable<string, array{array<string, mixed>}> */
    public static function provideInvalidConfigurations(): iterable
    {
        yield 'empty super admin role' => [['super_admin_role' => '']];
        yield 'empty read route pattern' => [['read_route_pattern' => '']];
        yield 'empty role label' => [['roles' => ['ROLE_API_ACCESS' => '']]];
        yield 'context without route prefixes' => [['contexts' => ['audit' => ['route_prefixes' => []]]]];
        yield 'retention lower than one day' => [['admin_action_log' => ['retention_days' => 0]]];
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private function process(array $config): array
    {
        return (new Processor())->processConfiguration(new Configuration(), [$config]);
    }
}
