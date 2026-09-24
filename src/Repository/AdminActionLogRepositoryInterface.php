<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\Repository;

use AKAWAKA\SyliusSecurityPlugin\Entity\AdminActionLogInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;

/**
 * @template T of AdminActionLogInterface
 * @extends RepositoryInterface<T>
 */
interface AdminActionLogRepositoryInterface extends RepositoryInterface
{
    /** @return int the number of deleted logs */
    public function deleteOlderThan(\DateTimeInterface $date): int;
}
