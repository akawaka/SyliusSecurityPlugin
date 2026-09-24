<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\AdminActionLog;

use AKAWAKA\SyliusSecurityPlugin\Entity\AdminActionLogInterface;
use Sylius\Component\Core\Model\AdminUserInterface;
use Sylius\Resource\Factory\FactoryInterface;
use Symfony\Component\HttpFoundation\Request;

/** @extends FactoryInterface<AdminActionLogInterface> */
interface AdminActionLogFactoryInterface extends FactoryInterface
{
    public function createFromRequest(
        string $type,
        Request $request,
        ?AdminUserInterface $adminUser,
        ?string $adminEmail = null,
    ): AdminActionLogInterface;
}
