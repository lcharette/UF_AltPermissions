<?php

/*
 * UF AltPermissions
 *
 * @link https://github.com/lcharette/UF-AltPermissions
 *
 * @copyright Copyright (c) 2016 Louis Charette
 * @license https://github.com/lcharette/UF-AltPermissions/blob/master/licenses/UserFrosting.md (MIT License)
 */

use League\FactoryMuffin\Faker\Facade as Faker;
use UserFrosting\Sprinkle\AltPermissions\Tests\Models\Foo;

/*
 * General factory for the User Model
 */
$fm->define(Foo::class)->setDefinitions([
    'name'        => Faker::sentence(3),
    'description' => Faker::text(),
]);
