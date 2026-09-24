<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\DependencyInjection;

use Sylius\Bundle\CoreBundle\DependencyInjection\PrependDoctrineMigrationsTrait;
use Sylius\Bundle\ResourceBundle\DependencyInjection\Extension\AbstractResourceExtension;
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
        $container->setParameter(
            'akawaka_sylius_security.contexts',
            array_replace(Configuration::DEFAULT_CONTEXTS, $config['contexts']),
        );

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.xml');
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
