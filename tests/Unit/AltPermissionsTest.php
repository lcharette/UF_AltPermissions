<?php

namespace UserFrosting\Tests\Unit;

use UserFrosting\Tests\Unit\AltPermissions;

class AltPermissionsTest extends AltPermissions
{
    protected $seeker = "foo";
    protected $seekerModel = "UserFrosting\Tests\Model\Foo";

    //!TODO : Use migration to create the `alt_foo` table at run time
}