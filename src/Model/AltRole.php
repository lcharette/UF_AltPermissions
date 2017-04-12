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
        //$this->permissions()->detach();

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

        return $this->belongsToMany($classMapper->getClassMapping('altPermission'), 'alt_permission_roles', 'role_id', 'permission_id')->withTimestamps();
    }

    /**
     * Get a list of users who have this role.
     */
    public function users()
    {
        /** @var UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = static::$ci->classMapper;

        return $this->belongsToMany($classMapper->getClassMapping('user'), 'alt_role_users', 'role_id', 'user_id')->withPivot('seeker_id');
    }

    /**
     * Model relation for dynamic seeker
     */
    public function auth($seeker)
    {
        $seekerClass = static::$ci->checkAuthSeeker->getSeekerModel($seeker);
        return $this->morphedByMany($seekerClass, 'seeker', 'alt_role_users', 'role_id')->withPivot('user_id');
    }

    /**
     * Model's getter
     *
     */

    /**
     * getStatusTxt function.
     * Prend le code de status et retourne la version localisé que le code représente
     *
     * @access public
     * @return void
     */
    public function getLocaleName()
    {
        return static::$ci->translator->translate($this->name);
    }
    public function getLocaleDescription()
    {
        return static::$ci->translator->translate($this->description);
    }

    /**
     * getRoute function.
     * Helper function for when the $ci is not directly avaiable
     *
     * @access public
     * @param string $routeName
     * @return Route for the designated route name
     */
    public function getRoute($routeName)
    {
        return static::$ci->router->pathFor($routeName, $this->toArray());
    }

    /**
     * Query scope to get all roles assigned to a specific seeker.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $seeker
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForAuth($query, $seeker)
    {
        return $query->where('seeker', $seeker);
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
}
