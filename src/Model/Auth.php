<?php
 /**
 * UF AltPermissions
 *
 * @link      https://github.com/lcharette/UF-AltPermissions
 * @copyright Copyright (c) 2016 Louis Charette
 * @license   https://github.com/lcharette/UF-AltPermissions/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace UserFrosting\Sprinkle\AltPermissions\Model;

use UserFrosting\Sprinkle\Core\Model\UFModel;

/**
 * Role Class
 *
 * Represents a role, which aggregates permissions and to which a user can be assigned.
 * @author Louis Charette (https://github.com/lcharette)
 * @property string slug
 * @property string name
 * @property string description
 */
class Auth extends UFModel
{
    /**
     * @var string The name of the table for the current model.
     */
    protected $table = "alt_role_users";

    public function seeker()
    {
        return $this->morphTo();
    }

    public function user()
    {
        $classMapper = static::$ci->classMapper;
        return $this->belongsTo($classMapper->getClassMapping('user'));
    }

    public function role()
    {
        $classMapper = static::$ci->classMapper;
        return $this->belongsTo($classMapper->getClassMapping('altRole'));
    }

    public function scopeForSeeker($query, $seeker, $seeker_id = false)
    {
        $seekerClass = static::$ci->checkAuthSeeker->getSeekerModel($seeker);
        $query = $query->where('seeker_type', $seekerClass);

        // Add the seeker id if we have it
        if ($seeker_id) {
           $query = $query->where('seeker_id', $seeker_id);
        }

        return $query;
    }
}
