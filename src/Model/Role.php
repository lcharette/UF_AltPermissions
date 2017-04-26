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
class Role extends UFModel
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
        $this->locale()->delete();

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

    public function auth($seeker = "")
    {
        if ($seeker != "")
        {
            $seekerClass = static::$ci->checkAuthSeeker->getSeekerModel($seeker);
            return $this->hasMany('UserFrosting\Sprinkle\AltPermissions\Model\Auth')->where('seeker_type', $seekerClass)->get();
        }
        else
        {
            return $this->hasMany('UserFrosting\Sprinkle\AltPermissions\Model\Auth');
        }
    }

    public function locale()
    {
        return $this->hasMany('UserFrosting\Sprinkle\AltPermissions\Model\RoleLocale');
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
        $data = $this->toArray();

        // Need to translate the seeker back into it's key value
        $data['seeker'] = static::$ci->checkAuthSeeker->getSeekerKey($this->seeker);

        return static::$ci->router->pathFor($routeName, $data);
    }

    /**
     * Model's Scope
     *
     */

    /**
     * Query scope to get all roles assigned to a specific seeker.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $seeker
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForSeeker($query, $seeker)
    {
        $seekerClass = static::$ci->checkAuthSeeker->getSeekerModel($seeker);
        return $query->where('seeker', $seekerClass);
    }

    /**
     * Misc
     */

    public function updateLocaleCache()
    {
        /** @var UserFrosting\I18n\MessageTranslator $translator */
        $translator = static::$ci->translator;

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

        // Define the data
        $data = [
            'locale' => $currentLocale,
            'name' => $translator->translate($this->name),
            'description' => $translator->translate($this->description)
        ];

        // update or create it
        $this->locale()->updateOrCreate($data, $data);
    }
}
