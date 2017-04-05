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
use Illuminate\Database\Eloquent\Relations\Pivot;

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
class AltRoleUser extends Pivot
{
    /**
     * @var string The name of the table for the current model.
     */
    protected $table = "alt_role_users";

    protected $fillable = [];

    /**
     * @var bool Enable timestamps for this class.
     */
    public $timestamps = true;

    /*public function seeker()
    {
        return $this->morphTo();
    }*/

    /*public function role()
    {
        $classMapper = static::$ci->classMapper;

        return $this->hasOne($classMapper->getClassMapping('altRole'), 'id', 'role_id');
    }*/

    /*public function user()
    {
        $classMapper = static::$ci->classMapper;

        return $this->hasOne($classMapper->getClassMapping('user'), 'id', 'user_id');
    }*/
}
