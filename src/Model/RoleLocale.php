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

    /**
     * scopeForCurrentLocale function.
     * Return the value for the current locale (either user or default locale)
     *
     * @access public
     * @param mixed $query
     * @return void
     */
    public function scopeForCurrentLocale($query)
    {
        /** @var UserFrosting\Sprinkle\Account\Model\User $currentUser */
        $currentUser = static::$ci->currentUser;

        /** @var UserFrosting\Config\Config $config */
        $config = static::$ci->config;

        // Get the current locale. Make sure to get the default (config) one if
        // there's no defined user yet
        if ($currentUser == null) {
            $currentLocale = $config['site.locales.default'];
        } else {
            $currentLocale = $currentUser->locale;
        }

        return $query->where('locale', $currentLocale);
    }
}
