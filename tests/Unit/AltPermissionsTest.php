<?php

/*
 * UF AltPermissions Sprinkle
 *
 * @author    Louis Charette
 * @copyright Copyright (c) 2018 Louis Charette
 * @link      https://github.com/lcharette/UF_AltPermissions
 * @license   https://github.com/lcharette/UF_AltPermissions/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\AltPermissions\Tests\Unit;

use UserFrosting\Sprinkle\AltPermissions\Tests\FooTableMigration;

class AltPermissionsTest extends AltPermissions
{
    use FooTableMigration;

    protected $seeker = 'foo';
    protected $seekerModel = "UserFrosting\Sprinkle\AltPermissions\Tests\Models\Foo";

    /**
     * setUp function.
     * Run the Foo Table migration.
     */
    protected function runTestMigrations()
    {
        // Run migrator to create the Foo table
        $this->runFooTableMigration();
    }
}
