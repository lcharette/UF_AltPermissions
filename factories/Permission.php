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

/*
 * General factory for the User Model
 */
$fm->define('UserFrosting\Sprinkle\AltPermissions\Database\Models\Permission')->setDefinitions([
    'name'        => Faker::sentence(3),
    'description' => Faker::text(),
    'slug'        => function ($object, $saved) {
        $slug = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $object->title);
        $slug = strtolower(trim($slug, '-'));
        $slug = preg_replace("/[\/_|+ -]+/", '-', $slug);

        return $slug;
    },
]);
