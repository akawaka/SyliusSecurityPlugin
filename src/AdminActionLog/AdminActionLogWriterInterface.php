<?php

declare(strict_types=1);

namespace AKAWAKA\SyliusSecurityPlugin\AdminActionLog;

use AKAWAKA\SyliusSecurityPlugin\Entity\AdminActionLogInterface;

interface AdminActionLogWriterInterface
{
    /** Saves the log immediately, without flushing the other pending changes of the entity manager. Never throws. */
    public function write(AdminActionLogInterface $log): void;
}
