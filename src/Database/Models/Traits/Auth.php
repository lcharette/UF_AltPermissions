<?php

/*
 * UF AltPermissions Sprinkle
 *
 * @author    Louis Charette
 * @copyright Copyright (c) 2018 Louis Charette
 * @link      https://github.com/lcharette/UF_AltPermissions
 * @license   https://github.com/lcharette/UF_AltPermissions/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\AltPermissions\Database\Models\Traits;

/**
 * AltPermissions Trait.
 *
 * Trait to add AltPermission Sprinkle methods to a sekker Model
 *
 * @author Louis Charette (https://github.com/lcharette)
 */
trait Auth
{
    public function auth()
    {
        return $this->morphMany('UserFrosting\Sprinkle\AltPermissions\Database\Models\Auth', 'seeker');
    }

    public function roleForUser($user_id)
    {
        return $this->auth()->where('user_id', $user_id)->first()->role;
    }
}
