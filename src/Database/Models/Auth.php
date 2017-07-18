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
 * Role Class
 *
 * Represents a role, which aggregates permissions and to which a user can be assigned.
 * @author Louis Charette (https://github.com/lcharette)
 * @property string slug
 * @property string name
 * @property string description
 */
class Auth extends Model
{
    /**
     * @var string The name of the table for the current model.
     */
    protected $table = "alt_role_users";

    /**
     * @var bool Enable timestamps for this class.
     */
    public $timestamps = true;

    /**
     * @var array The fields of the table for the current model.
     * N.B.: The only field that is allowed to change it the role, AKA the only one
     * that leads to a single element. You won't change a user of seeker.
     */
    protected $fillable = [
        "role_id",
        "user_id",
        "seeker_id",
        "seeker_type"
    ];

    /**
     * Seeker relation. Morph back to the seeker model
     */
    public function seeker()
    {
        return $this->morphTo();
    }

    /**
     * User relation. Link back to the user Model.
     */
    public function user()
    {
        $classMapper = static::$ci->classMapper;
        return $this->belongsTo($classMapper->getClassMapping('user'));
    }

    /**
     * Role relation. Link back to the AltRole model
     */
    public function role()
    {
        $classMapper = static::$ci->classMapper;
        return $this->belongsTo($classMapper->getClassMapping('altRole'));
    }

    /**
     * forSeeker scope. Use it to get rows for a specific seeker
     *
     * @param mixed $query
     * @param string $seeker
     * @param int $seeker_id (default: 0)
     * @return $query
     */
    public function scopeForSeeker($query, $seeker, $seeker_id = 0)
    {
        $seekerClass = static::$ci->auth->getSeekerModel($seeker);
        $query = $query->where('seeker_type', $seekerClass);

        // Add the seeker id if we have it
        if ($seeker_id) {
           $query = $query->where('seeker_id', $seeker_id);
        }

        return $query;
    }

    /**
     * Joins the user, so we can do things like sort, search, paginate, etc.
     * @param mixed $query
     * @return $query
     */
    public function scopeJoinUser($query)
    {
        $query = $query->leftJoin('users', $this->table.'.user_id', '=', 'users.id');
        return $query;
    }

    /**
     * Joins the role, so we can do things like sort, search, paginate, etc.
     * @param mixed $query
     * @return $query
     */
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
