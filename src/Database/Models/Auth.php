<?php
 /**
 * UF AltPermissions
 *
 * @link      https://github.com/lcharette/UF-AltPermissions
 * @copyright Copyright (c) 2016 Louis Charette
 * @license   https://github.com/lcharette/UF-AltPermissions/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace UserFrosting\Sprinkle\AltPermissions\Database\Models;

use UserFrosting\Sprinkle\Core\Models\UFModel;

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

    /**
     * @var array The fields of the table for the current model.
     * N.B.: The only field that is allowed to change it the role, AKA the only one
     * that leads to a single element. You won't change a user of seeker.
     */
    protected $fillable = [
        "role_id"
    ];

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

    /**
     * Joins the user, so we can do things like sort, search, paginate, etc.
     */
    public function scopeJoinUser($query)
    {
        $query = $query->leftJoin('users', $this->table.'.user_id', '=', 'users.id');
        return $query;
    }

    public function scopeJoinRole($query)
    {
        $query = $query->leftJoin('alt_roles', $this->table.'.role_id', '=', 'alt_roles.id');
        return $query;
    }

    /**
     * getRoute function.
     * Helper function for when the $ci is not directly avaiable
     *
     * @access public
     * @param string $routeName
     * @param mixed $args (default: [])
     * @return Route for the designated route name
     */
    public function getRoute($routeName, $args = [])
    {
        $args = (empty($args)) ? $this->toArray() : $args;
        return static::$ci->router->pathFor($routeName, $args);
    }
}
