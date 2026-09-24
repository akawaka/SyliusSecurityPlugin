<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\DependencyInjection;

use AKAWAKA\SyliusSecurityPlugin\Security\AdminContext;
use Sylius\Bundle\CoreBundle\DependencyInjection\PrependDoctrineMigrationsTrait;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
use Sylius\Component\Core\Model\AdminUserInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class AkawakaSyliusSecurityExtension extends AbstractResourceExtension implements PrependExtensionInterface
{
    use PrependDoctrineMigrationsTrait;

    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration([], $container), $configs);

        $container->setParameter('akawaka_sylius_security.super_admin_role', $config['super_admin_role']);
        $container->setParameter('akawaka_sylius_security.read_route_pattern', $config['read_route_pattern']);
        $container->setParameter(
            'akawaka_sylius_security.public_route_prefixes',
            array_values(array_unique(array_merge(Configuration::DEFAULT_PUBLIC_ROUTE_PREFIXES, $config['public_route_prefixes']))),
        );
        $contexts = array_replace(Configuration::DEFAULT_CONTEXTS, $config['contexts']);
        $container->setParameter('akawaka_sylius_security.contexts', $contexts);

        $this->validateRoles($config['roles'], $config['super_admin_role'], $contexts);
        $container->setParameter('akawaka_sylius_security.roles', $config['roles']);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.xml');
    }

    /**
     * @param array<string, string> $roles
     * @param array<string, array{route_prefixes: list<string>}> $contexts
     */
    private function validateRoles(array $roles, string $superAdminRole, array $contexts): void
    {
        $reservedRoles = [AdminUserInterface::DEFAULT_ADMIN_ROLE, $superAdminRole];
        foreach ($contexts as $code => $context) {
            $adminContext = new AdminContext($code, $context['route_prefixes']);
            $reservedRoles[] = $adminContext->getReadRole();
            $reservedRoles[] = $adminContext->getWriteRole();
        }

        foreach (array_keys($roles) as $role) {
            if (!str_starts_with($role, 'ROLE_')) {
                throw new InvalidConfigurationException(sprintf('The role "%s" configured in "akawaka_sylius_security.roles" must start with "ROLE_".', $role));
            }

            if (in_array($role, $reservedRoles, true)) {
                throw new InvalidConfigurationException(sprintf('The role "%s" is reserved and cannot be configured in "akawaka_sylius_security.roles".', $role));
            }
        }
    }

    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        return new Configuration();
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->prependDoctrineMigrations($container);
    }

    protected function getMigrationsNamespace(): string
    {
        return 'DoctrineMigrations';
    }

    protected function getMigrationsDirectory(): string
    {
        return '@AkawakaSyliusSecurityPlugin/migrations';
    }

    protected function getNamespacesOfMigrationsExecutedBefore(): array
    {
        return [
            'Sylius\Bundle\CoreBundle\Migrations',
        ];
    }
}
