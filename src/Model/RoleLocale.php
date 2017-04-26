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
class RoleLocale extends UFModel
{
    /**
     * @var string The name of the table for the current model.
     */
    protected $table = "alt_roles_locale";

    protected $fillable = [
        "locale",
        "name",
        "description"
    ];

    /**
     * @var bool Enable timestamps for this class.
     */
    public $timestamps = true;
}
