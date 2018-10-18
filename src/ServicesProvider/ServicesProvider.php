<?php

/*
 * UF AltPermissions
 *
 * @link https://github.com/lcharette/UF-AltPermissions
 *
 * @copyright Copyright (c) 2016 Louis Charette
 * @license https://github.com/lcharette/UF-AltPermissions/blob/master/licenses/UserFrosting.md (MIT License)
 */

namespace UserFrosting\Sprinkle\AltPermissions\ServicesProvider;

use UserFrosting\Sprinkle\AltPermissions\AccessControlLayer;
use UserFrosting\Sprinkle\AltPermissions\Middleware\CheckAuthSeeker;

/**
 * Registers services for the AltPermissions sprinkle, such as classmapper, etc.
 *
 * @author Louis Charette (https://github.com/lcharette)
 */
class ServicesProvider
{
    /**
     * Register UserFrosting's account services.
     *
     * @param Container $container A DI container implementing ArrayAccess and container-interop.
     */
    public function register($container)
    {

        /*
         * Extend the 'classMapper' service to register model classes.
         *
         * Mappings added: User, Group, Role, Permission, Activity, PasswordReset, Verification
         */
        $container->extend('classMapper', function ($classMapper, $c) {
            $classMapper->setClassMapping('altRole', 'UserFrosting\Sprinkle\AltPermissions\Database\Models\Role');
            $classMapper->setClassMapping('altPermission', 'UserFrosting\Sprinkle\AltPermissions\Database\Models\Permission');
            $classMapper->setClassMapping('altAuth', 'UserFrosting\Sprinkle\AltPermissions\Database\Models\Auth');
            $classMapper->setClassMapping('altUser', 'UserFrosting\Sprinkle\AltPermissions\Database\Models\User');
            $classMapper->setClassMapping('altRole_sprunje', 'UserFrosting\Sprinkle\AltPermissions\Sprunje\RoleSprunje');
            $classMapper->setClassMapping('auth_sprunje', 'UserFrosting\Sprinkle\AltPermissions\Sprunje\AuthSprunje');
            $classMapper->setClassMapping('authUser_sprunje', 'UserFrosting\Sprinkle\AltPermissions\Sprunje\AuthUsersSprunje');

            return $classMapper;
        });

        /*
         * Middleware to check environment.
         *
         * @todo We should cache the results of this, the first time that it succeeds.
         */
        $container['checkAuthSeeker'] = function ($c) {
            $checkAuthSeeker = new CheckAuthSeeker($c->config['AltPermissions']);

            return $checkAuthSeeker;
        };

        $container['acl'] = function ($c) {
            return new AccessControlLayer($c->config);
        };
    }
}
