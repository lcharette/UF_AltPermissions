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

/*
 * General factory for the User Model
 */
$fm->define('UserFrosting\Sprinkle\AltPermissions\Database\Models\User')->setDefinitions([
    'user_name'     => Faker::firstNameMale(),
    'first_name'    => Faker::firstNameMale(),
    'last_name'     => Faker::firstNameMale(),
    'email'         => Faker::email(),
    'locale'        => 'en_US',
    'flag_verified' => 1,
    'flag_enabled'  => 1,
    'password'      => Faker::password(),
]);
