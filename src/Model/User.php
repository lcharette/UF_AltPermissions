<?php
 /**
 * UF AltPermissions
 *
 * @link      https://github.com/lcharette/UF-AltPermissions
 * @copyright Copyright (c) 2016 Louis Charette
 * @license   https://github.com/lcharette/UF-AltPermissions/blob/master/licenses/UserFrosting.md (MIT License)
 */

namespace UserFrosting\Sprinkle\AltPermissions\Model;

use UserFrosting\Sprinkle\Account\Model\User as CoreUser;

/**
 * AltPermissionUser
 *
 * Trait to add AltPermission Sprinkle methods to the core User Model
 * @author Louis Charette (https://github.com/lcharette)
 */
class User extends CoreUser
{
    public function auth($seeker)
    {
        $seekerClass = static::$ci->checkAuthSeeker->getSeekerModel($seeker);
        return $this->morphedByMany($seekerClass, 'seeker', 'alt_role_users')->withPivot('role_id');
    }

    /*public function scopeForAuth($query, $seeker)
    {
        return $query->where('seeker', $seeker);
    }*/

    public function altRole()
    {
        $classMapper = static::$ci->classMapper;
        return $this->belongsToMany($classMapper->getClassMapping('altRole'), 'alt_role_users', 'user_id', 'role_id')->withPivot('seeker_id');
    }
}
