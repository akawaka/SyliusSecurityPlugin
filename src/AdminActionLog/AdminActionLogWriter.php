<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\AdminActionLog;

use AKAWAKA\SyliusSecurityPlugin\Entity\AdminActionLogInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Psr\Log\LoggerInterface;

/**
 * Inserts the log with a plain DBAL query: flushing the entity manager would also save the entities
 * modified during the request, e.g. by a form that failed validation.
 */
final class AdminActionLogWriter implements AdminActionLogWriterInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function write(AdminActionLogInterface $log): void
    {
        try {
            $metadata = $this->entityManager->getClassMetadata($log::class);
            $connection = $this->entityManager->getConnection();

            $data = [];
            $types = [];
            foreach ($metadata->getFieldNames() as $field) {
                if ($metadata->isIdentifier($field)) {
                    continue;
                }

                $column = $connection->quoteIdentifier($metadata->getColumnName($field));
                $data[$column] = $metadata->getFieldValue($log, $field);

                $type = $metadata->getTypeOfField($field);
                if (null !== $type) {
                    $types[$column] = $type;
                }
            }

            foreach ($metadata->getAssociationNames() as $association) {
                if (!$metadata->isSingleValuedAssociation($association) || !$metadata->isAssociationWithSingleJoinColumn($association)) {
                    continue;
                }

                $column = $connection->quoteIdentifier($metadata->getSingleAssociationJoinColumnName($association));
                $data[$column] = $this->getIdentifier($metadata->getFieldValue($log, $association));
            }

            $connection->insert($connection->quoteIdentifier($metadata->getTableName()), $data, $types);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to save the administrator action log: {message}', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);
        }
    }

    private function getIdentifier(mixed $entity): mixed
    {
        if (!is_object($entity)) {
            return null;
        }

        /** @var ClassMetadata<object> $metadata */
        $metadata = $this->entityManager->getClassMetadata($entity::class);
        $identifier = $metadata->getIdentifierValues($entity);

        return [] === $identifier ? null : reset($identifier);
    }
}
