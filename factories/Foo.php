<?php

/*
 * UF AltPermissions Sprinkle
 *
 * @author    Louis Charette
 * @copyright Copyright (c) 2018 Louis Charette
 * @link      https://github.com/lcharette/UF_AltPermissions
 * @license   https://github.com/lcharette/UF_AltPermissions/blob/master/LICENSE.md (MIT License)
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
