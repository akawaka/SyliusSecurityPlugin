<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\Repository;

use AKAWAKA\SyliusSecurityPlugin\Entity\AdminActionLogInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

/**
 * @template T of AdminActionLogInterface
 * @implements AdminActionLogRepositoryInterface<T>
 */
class AdminActionLogRepository extends EntityRepository implements AdminActionLogRepositoryInterface
{
    public function deleteOlderThan(\DateTimeInterface $date): int
    {
        $deleted = $this->createQueryBuilder('o')
            ->delete()
            ->andWhere('o.createdAt < :date')
            ->setParameter('date', \DateTimeImmutable::createFromInterface($date), 'datetime_immutable')
            ->getQuery()
            ->execute()
        ;

        return is_int($deleted) ? $deleted : 0;
    }
}
