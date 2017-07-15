<?php
 /**
 * UF AltPermissions
 *
 * @link      https://github.com/lcharette/UF-AltPermissions
 * @copyright Copyright (c) 2016 Louis Charette
 * @license   https://github.com/lcharette/UF-AltPermissions/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace UserFrosting\Sprinkle\AltPermissions;

use UserFrosting\Sprinkle\AltPermissions\Database\Models\Auth;
use UserFrosting\Sprinkle\AltPermissions\Database\Models\Permission;
use UserFrosting\Sprinkle\Core\Facades\Debug;

/**
 * Bootstrapper class for the 'AltPermissions' sprinkle.
 *
 * @author Louis Charette (https://github.com/lcharette)
 */
class AuthManager
{
    /**
     * @var ContainerInterface The global container object, which holds all your services.
     */
    protected $ci;

    /**
     * @var bool Is the auth debug config is on.
     */
    protected $debug;

    /**
     * Create a new AuthorizationManager object.
     *
     * @param ContainerInterface $ci The global container object, which holds all your services.
     */
    public function __construct($ci)
    {
        $this->ci = $ci;

        // Set debug state
        $this->debug = $this->ci->config['debug.auth'];
    }

    /*
      NOTES:
      - To make it even more efficient, all user "ON" permission could be put in cache. We fetch the roles for a user, then cache the result based on seeker id?

      PHPBB
      - function acl_get($opt, $f = 0) Look up an option
      - function acl_getf($opt, $clean = false) Get forums with the specified permission setting
      - function acl_gets() Get permission settings (more than one)
      - function acl_get_list($user_id = false, $opts = false, $forum_id = false) Get permission listing based on user_id/options/forum_ids
    */



    /**
     * hasPermission function.
     *
     * Return true or false if the user have the specified permission set
     * to `on` for the specified seeker id.
     *
     * N.B.: Note that this method doesn't require the `seeker_type`, as the
     * permission slug is forced to be bound to the same seeker_type as the
     * info in the `Auth` table
     *
     * @access public
     * @param mixed $user The user model we want to perform the auth check on
     * @param mixed $slug The permission slug
     * @param mixed $seeker_id The seeker id
     * @return bool User has permission or not
     *
     * !TODO : The slug must accept an array
     */
    public function hasPermission($user, $slug, $seeker_id)
    {
        if ($this->debug) {
            $trace = array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3), 1);
            $this->ci->authLogger->debug("Authorization check requested at: ", $trace);
            $this->ci->authLogger->debug("Checking authorization for user {$user->id} ('{$user->user_name}') on permission '$slug' and seeker id '$seeker_id'...");
        }

        // We find the permission related to that slug
        /*$permission = $this->cache->rememberForever("auth.permissions.$slug", function () use ($slug) {
            return Permission::with('roles')->where('slug', $slug)->first();
        });*/

        // Super mighty query
        $permission = Permission::whereHas('roles.auth', function ($query) use ($user, $seeker_id) {
            $query->where(['user_id' => $user->id, 'seeker_id' => $seeker_id]);
        })->where('slug', $slug)->first();

        // !TODO :: This result should be cached

        // If the above query returned something, then it's a match!
        // Otherwise, might be because the slug doesn't exist, the user is bad, the seeker is bad, etc.
        // In any of those cases, it will be false anyway
        return ($permission ? true : false);

    }

    /**
     * getSeekersForPermissions function.
     *
     * This method returns a list of seeker ids where the
     * selected user has a role containing a permission defined in `$slug`
     * The goal here is to get a list of seekers ids the user have that permission set to "on"
     *
     * @access public
     * @param mixed $user The user model we want to perform the auth check on
     * @param mixed $slug The permission slug
     * @return array A list of Seekers ID
     */
    public function getSeekersForPermission($user, $slug)
    {
        // Display initial debug statement
        if ($this->debug) {
            $trace = array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3), 1);
            $this->ci->authLogger->debug("Seekers for permission authorization list requested at: ", $trace);
            $this->ci->authLogger->debug("Getting all seekers for user {$user->id} ('{$user->user_name}') on permission '$slug'...");
        }

        // Query the `Auth` Model. We start by getting all the rows specific to this user
        // Once we have a list of auth for that user, we only get the auth that contain
        // a role containing the permission slug we are after. This last part is
        // done on the `whereHas` function on the `Auth` relation (ask permission relation throught the `role` relation)
        $authorizedSeekers = Auth::where('user_id', $user->id)->whereHas('role.permissions', function ($query) use ($slug) {
            $query->where(['slug' => $slug]);
        })->select(['seeker_id', 'seeker_type'])->get();

        // !TODO : Cache the result

        // We send the result to the debug
        if ($this->debug) {
            $this->ci->authLogger->debug("Autorisation for seekers id {$authorizedSeekers->pluck('seeker_id')}");
        }

        // Done !
        return $authorizedSeekers;
    }

    /**
     * getPermissionsForSeeker function.
     *
     * Return a list of permissions slugs the users have for a specific seeker id
     *
     * N.B.: Note that this method DOES require the `seeker_type`, as a user might
     * have in the `Auth` table two roles for the same value of `seeker_id`,
     * but different `seeker_id, seeker_type` combinaison.
     *
     * @access public
     * @param UserModel $user The user model we want to perform the auth check on
     * @param int $seeker_id The seeker id
     * @param string $seeker_type The seeker type (string slug or full class. See next params)
     * @param bool $getSeekerClass (default: false) Set to false if `$seeker_type` is already the full class
     * @return Collection A collection
     */
    public function getPermissionsForSeeker($user, $seeker_id, $seeker_type, $getSeekerClass = true)
    {
        // Display initial debug statement
        if ($this->debug) {
            $trace = array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3), 1);
            $this->ci->authLogger->debug("Permissions for seeker authorization list requested at: ", $trace);
            $this->ci->authLogger->debug("Getting all permissions for user {$user->id} ('{$user->user_name}') on seekers '$seeker_id' or type `$seeker_type`...");
        }

        // Get full seeker class name
        if ($getSeekerClass) {
            $seeker_type = $this->ci->checkAuthSeeker->getSeekerModel($seeker_type);
        }

        // Query the `Auth` Model. We start by getting the rows specific to the
        // requested user and seeker_id. Since a user can only have one role per
        // individual seeker, we'll always get 1 or 0 role. Then it's just a
        // matter of finding the permissions associated with this role
        $auth = Auth::where([
            'user_id' => $user->id,
            'seeker_id' => $seeker_id,
            'seeker_type' => $seeker_type
        ])->with(['role', 'role.permissions'])->first();

        // !TODO : Cache the result

        // Dive down to the permissions collection
        $permissions = $auth->role->permissions;

        // We send the result to the debug
        if ($this->debug) {
            $this->ci->authLogger->debug("Autorisation for seekers id {$permissions->pluck('slug')}");
        }

        // Done !
        return $permissions;
    }

    // !TODO
    // public function getPermissions -> Return same as `getPermissionsForSeeker`, but as a multidimentaionnal array of `seeker => [permissions]`
}
