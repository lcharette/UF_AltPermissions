<?php
 /**
 * UF AltPermissions
 *
 * @link      https://github.com/lcharette/UF-AltPermissions
 * @copyright Copyright (c) 2016 Louis Charette
 * @license   https://github.com/lcharette/UF-AltPermissions/blob/master/licenses/UserFrosting.md (MIT License)
 */

namespace UserFrosting\Sprinkle\AltPermissions\Model\Traits;

/**
 * AltPermissions Trait
 *
 * Trait to add AltPermission Sprinkle methods to a sekker Model
 * @author Louis Charette (https://github.com/lcharette)
 */
Trait AltPermissions
{
    public function users()
    {
        $classMapper = static::$ci->classMapper;
        return $this->morphToMany($classMapper->getClassMapping('user'), 'seeker', 'alt_role_users')->withPivot('role_id')->withTimestamps();
        //return $this->morphToMany($classMapper->getClassMapping('user'), 'seeker', 'alt_role_users')->using('UserFrosting\Sprinkle\AltPermissions\Model\AltRoleUser');
    }

    public function roles()
    {
        $classMapper = static::$ci->classMapper;
        return $this->morphToMany($classMapper->getClassMapping('altRole'), 'seeker', 'alt_role_users', null, 'role_id')->withPivot('user_id')->withTimestamps();
        //return $this->morphToMany($classMapper->getClassMapping('user'), 'seeker', 'alt_role_users')->using('UserFrosting\Sprinkle\AltPermissions\Model\AltRoleUser');
    }
}
