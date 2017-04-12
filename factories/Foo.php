<?php
/**
 * UserFrosting (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/UserFrosting
 * @copyright Copyright (c) 2013-2016 Alexander Weissman
 * @license   https://github.com/userfrosting/UserFrosting/blob/master/licenses/UserFrosting.md (MIT License)
 */

use League\FactoryMuffin\Faker\Facade as Faker;


/**
 * General factory for the User Model
 */
$fm->define('UserFrosting\Tests\Model\Foo')->setDefinitions([
    'name' => Faker::sentence(3),
    'description' => Faker::text()
]);