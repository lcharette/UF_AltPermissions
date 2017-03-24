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
class AltRole extends UFModel
{
    /**
     * @var string The name of the table for the current model.
     */
    protected $table = "alt_roles";

    protected $fillable = [
        "slug",
        "seeker",
        "name",
        "description"
    ];

    /**
     * @var bool Enable timestamps for this class.
     */
    public $timestamps = true;

    /**
     * Delete this role from the database, removing associations with permissions and users.
     *
     */
    public function delete()
    {
        // Remove all permission associations
        $this->permissions()->detach();

        // Remove all user associations
        //$this->users()->detach();

        // Delete the role
        $result = parent::delete();

        return $result;
    }

    /**
     * Get a list of permissions assigned to this role.
     */
    public function permissions()
    {
        /** @var UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = static::$ci->classMapper;

        return $this->belongsToMany($classMapper->getClassMapping('altPermission'), 'alt_permission_roles', 'alt_role_id', 'alt_permission_id')->withTimestamps();
    }

    /**
     * Query scope to get all roles assigned to a specific user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    /*public function scopeForUser($query, $userId)
    {
        return $query->join('role_users', function ($join) use ($userId) {
            $join->on('role_users.role_id', 'roles.id')
                 ->where('user_id', $userId);
        });
    }*/

    /**
     * Get a list of users who have this role.
     */
    /*public function users()
    {
        /** @var UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper * /
        $classMapper = static::$ci->classMapper;

        return $this->belongsToMany($classMapper->getClassMapping('user'), 'role_users', 'role_id', 'user_id');
    }*/
}
