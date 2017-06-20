<?php
 /**
 * UF AltPermissions
 *
 * @link      https://github.com/lcharette/UF-AltPermissions
 * @copyright Copyright (c) 2016 Louis Charette
 * @license   https://github.com/lcharette/UF-AltPermissions/blob/master/licenses/UserFrosting.md (MIT License)
 */

namespace UserFrosting\Sprinkle\AltPermissions\Database\Models\Traits;

/**
 * AltPermissions Trait
 *
 * Trait to add AltPermission Sprinkle methods to a sekker Model
 * @author Louis Charette (https://github.com/lcharette)
 */
Trait Auth
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
