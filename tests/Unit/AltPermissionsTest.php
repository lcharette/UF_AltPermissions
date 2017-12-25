<?php
/**
* UF AltPermissions
*
* @link      https://github.com/lcharette/UF-AltPermissions
* @copyright Copyright (c) 2016 Louis Charette
* @license   https://github.com/lcharette/UF-AltPermissions/blob/master/licenses/UserFrosting.md (MIT License)
*/
namespace UserFrosting\Sprinkle\AltPermissions\Tests\Unit;

use UserFrosting\Sprinkle\AltPermissions\Tests\Unit\AltPermissions;
use UserFrosting\Sprinkle\AltPermissions\Tests\FooTableMigration;

class AltPermissionsTest extends AltPermissions
{
    use FooTableMigration;

    protected $seeker = "foo";
    protected $seekerModel = "UserFrosting\Sprinkle\AltPermissions\Tests\Models\Foo";

    /**
     * setUp function.
     * Run the Foo Table migration
     */
    protected function runTestMigrations()
    {
        // Run migrator to create the Foo table
        $this->runFooTableMigration();
    }
}