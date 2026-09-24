<?php

declare(strict_types=1);

namespace spec\AKAWAKA\SyliusSecurityPlugin\Security;

use AKAWAKA\SyliusSecurityPlugin\Security\AdminContextRegistryInterface;
use PhpSpec\ObjectBehavior;

final class AdminContextRegistrySpec extends ObjectBehavior
{
    function let(): void
    {
        $this->beConstructedWith(
            ['sylius_admin_dashboard', 'sylius_admin_ajax_'],
            [
                'catalog' => ['route_prefixes' => ['sylius_admin_product', 'sylius_admin_ajax_taxon_move']],
                'marketing' => ['route_prefixes' => ['sylius_admin_product_review']],
            ],
        );
    }

    function it_is_an_admin_context_registry(): void
    {
        $this->shouldImplement(AdminContextRegistryInterface::class);
    }

    function it_exposes_configured_contexts(): void
    {
        $this->all()->shouldHaveCount(2);
        $this->all()->shouldHaveKey('catalog');
    }

    function it_resolves_a_context_by_route_prefix(): void
    {
        $this->resolveByRoute('sylius_admin_product_index')->getCode()->shouldReturn('catalog');
    }

    function it_resolves_the_longest_matching_prefix(): void
    {
        $this->resolveByRoute('sylius_admin_product_review_index')->getCode()->shouldReturn('marketing');
        $this->resolveByRoute('sylius_admin_ajax_taxon_move_up')->getCode()->shouldReturn('catalog');
    }

    function it_returns_null_for_unmapped_or_public_routes(): void
    {
        $this->resolveByRoute('sylius_admin_order_index')->shouldReturn(null);
        $this->resolveByRoute('sylius_admin_ajax_product_by_code')->shouldReturn(null);
    }

    function it_tells_whether_a_route_is_public(): void
    {
        $this->isPublicRoute('sylius_admin_dashboard')->shouldReturn(true);
        $this->isPublicRoute('sylius_admin_ajax_product_by_code')->shouldReturn(true);
        $this->isPublicRoute('sylius_admin_ajax_taxon_move')->shouldReturn(false);
        $this->isPublicRoute('sylius_admin_order_index')->shouldReturn(false);
    }
}
