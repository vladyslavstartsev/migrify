<?php

declare(strict_types=1);

namespace Migrify\ClassPresence\HttpKernel;

use Migrify\MigrifyKernel\HttpKernel\AbstractMigrifyKernel;
use Symfony\Component\Config\Loader\LoaderInterface;

final class ClassPresenceKernel extends AbstractMigrifyKernel
{
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__ . '/../../config/config.php');
    }
}
