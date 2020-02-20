<?php

/*
 * UF AltPermissions Sprinkle
 *
 * @author    Louis Charette
 * @copyright Copyright (c) 2018 Louis Charette
 * @link      https://github.com/lcharette/UF_AltPermissions
 * @license   https://github.com/lcharette/UF_AltPermissions/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\AltPermissions\Database\Models;

use UserFrosting\Sprinkle\Account\Database\Models\User as CoreUser;

/**
 * AltPermissionUser.
 *
 * Trait to add AltPermission Sprinkle methods to the core User Model
 *
 * @author Louis Charette (https://github.com/lcharette)
 */
class User extends CoreUser
{
    /**
     * seeker relation. Link to the seeker model using the polymorphic relation.
     */
    public function seeker($seeker)
    {
        $seekerClass = static::$ci->acl->getSeekerModel($seeker);

        return $this->morphedByMany($seekerClass, 'seeker', 'alt_role_users')->withPivot('role_id');
    }

    /**
     * auth relation. Link to the auth model (man-to-many) relation. User $seeker to restrict rows to a specific seeker class.
     *
     * @param string $seeker (default: "") seeker name
     */
    public function auth($seeker = '')
    {
        if ($seeker != '') {
            $seekerClass = static::$ci->acl->getSeekerModel($seeker);

            return $this->hasMany('UserFrosting\Sprinkle\AltPermissions\Database\Models\Auth')->where('seeker_type', $seekerClass)->get();
        } else {
            return $this->hasMany('UserFrosting\Sprinkle\AltPermissions\Database\Models\Auth');
        }
    }

    /**
     * roleForSeeker. Use it to get the user role for a specific seeker.
     *
     * @param string $seeker
     * @param int    $seeker_id
     *
     * @return Role Model or false if no role found
     */
    public function roleForSeeker($seeker, $seeker_id)
    {
        // Get the auth for the requested seeker
        $auth = $this->auth($seeker)->where('seeker_id', $seeker_id)->first();

        // If we found something, return the role. Otherwise, return false
        return ($auth) ? $auth->role : false;
    }
}
