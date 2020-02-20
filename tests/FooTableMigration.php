<?php

/*
 * UF AltPermissions Sprinkle
 *
 * @author    Louis Charette
 * @copyright Copyright (c) 2018 Louis Charette
 * @link      https://github.com/lcharette/UF_AltPermissions
 * @license   https://github.com/lcharette/UF_AltPermissions/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\AltPermissions\Tests;

use UserFrosting\Sprinkle\Core\Database\Migrator\MigrationLocatorInterface;

trait FooTableMigration
{
    public function runFooTableMigration()
    {
        $migrator = $this->ci->migrator;
        $migrator->setLocator(new migrationLocatorStub());
        $result = $migrator->run();

        $this->assertEquals(['\\UserFrosting\\Sprinkle\\AltPermissions\\Tests\\Migrations\\AltFooTable'], $result);
    }
}

/**
 *    This stub contain migration which order they need to be run is different
 *    than the order the file are returned because of dependencies management.
 */
class migrationLocatorStub implements MigrationLocatorInterface
{
    public function getMigrationsForSprinkle($sprinkleName)
    {
    }

    public function getMigrations()
    {
        return [
            '\\UserFrosting\\Sprinkle\\AltPermissions\\Tests\\Migrations\\AltFooTable',
        ];
    }
}
