<?php
 /**
 * UF AltPermissions
 *
 * @link      https://github.com/lcharette/UF-AltPermissions
 * @copyright Copyright (c) 2016 Louis Charette
 * @license   https://github.com/lcharette/UF-AltPermissions/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace UserFrosting\Sprinkle\AltPermissions\Database\Models;

use UserFrosting\Sprinkle\Core\Database\Models\Model;

/**
 * Permission Class.
 *
 * Represents a permission for a role.
 * @author Louis Charette (https://github.com/lcharette)
 * @property string slug
 * @property string name
 * @property string conditions
 * @property string description
 */
class Permission extends Model
{
    /**
     * @var string The name of the table for the current model.
     */
    protected $table = "alt_permissions";

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
     * Delete this permission from the database, removing associations with roles.
     *
     */
    public function delete()
    {
        // Remove all role associations
        $this->roles()->detach();

        // Delete the permission
        $result = parent::delete();

        return $result;
    }

    /**
     * Get a list of roles to which this permission is assigned.
     */
    public function roles()
    {
        /** @var UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = static::$ci->classMapper;

        return $this->belongsToMany($classMapper->getClassMapping('altRole'), 'alt_permission_roles', 'permission_id', 'role_id')->withTimestamps();
    }

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
     * Query scope to get all roles assigned to a specific seeker.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $seeker
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForSeeker($query, $seeker)
    {
        $seekerClass = static::$ci->acl->getSeekerModel($seeker);
        return $query->where('seeker', $seekerClass);
    }

    /**
     * Query scope to get all permissions assigned to a specific role.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $roleId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    /*public function scopeForRole($query, $roleId)
    {
        return $query->join('permission_roles', function ($join) use ($roleId) {
            $join->on('permission_roles.permission_id', 'permissions.id')
                 ->where('role_id', $roleId);
        });
    }*/

    /**
     * Query scope to get all permissions NOT associated with a specific role.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $roleId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    /*public function scopeNotForRole($query, $roleId)
    {
        return $query->join('permission_roles', function ($join) use ($roleId) {
            $join->on('permission_roles.permission_id', 'permissions.id')
                 ->where('role_id', '!=', $roleId);
        });
    }*/
}
