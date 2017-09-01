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
    protected $config;

    /**
     * @var bool Is the auth debug config is on.
     */
    protected $debug;

    /**
     * Create a new AuthorizationManager object.
     *
     * @param ContainerInterface $ci The global container object, which holds all your services.
     */
    public function __construct($config)
    {
        $this->config = $config;
        $this->debug = $this->config['debug.auth'];
    }

    /**
     * getSeekerModel function.
     * Returns the model associated with a seeker name
     *
     * @access public
     * @param string $seeker The Seeker name
     * @return string The seeker full class name
     */
    public function getSeekerModel($seeker)
    {
        if ($seeker == "" || !array_key_exists($seeker, $this->config['AltPermissions.seekers'])) {
            throw new \InvalidArgumentException("Seeker '$seeker' not found");
        } else {
            //!TODO : Check class exist
            return $this->config['AltPermissions.seekers'][$seeker];
        }
    }

    /**
     * getSeekerKey function.
     * Returns the model associated with a seeker name
     *
     * @access public
     * @param string $seekerModel The seeker full class name
     * @return string The Seeker name
     */
    public function getSeekerKey($seekerModel)
    {
        $config = array_flip($this->config['AltPermissions.seekers']);

        if ($seekerModel == "" || !array_key_exists($seekerModel, $config)) {
            throw new \InvalidArgumentException("Seeker '$seekerModel' not found");
        } else {
            return $config[$seekerModel];
        }
    }

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
            Debug::debug("Authorization check requested at: ", $trace);
            Debug::debug("Checking authorization for user {$user->id} ('{$user->user_name}') on permission '$slug' and seeker id '$seeker_id'...");
        }

        // The master (root) account has access to everything.
        if ($user->id == $this->config['reserved_user_ids.master']) {
            if ($debug) {
                Debug::debug("User is the master (root) user.  Access granted.");
            }
            return true;
        }

        // We find the permission related to that slug
        //!TODO : Do some caching
        /*$permission = $this->cache->rememberForever("auth.permissions.$slug", function () use ($slug) {
            return Permission::with('roles')->where('slug', $slug)->first();
        });*/

        // Build the Eloquent query
        // We start by limiting the slug. This will limit the number of relation we query next
        // The `orWhere` need to be in a bracket, otherwise it will create false positivewith `wherehas`
        // See http://laraveldaily.com/and-or-and-brackets-with-eloquent/
        $query = Permission::where(function ($query) use ($slug) {
            $query->where('slug', $slug)->orWhere('slug', 'like', $slug . '.%');
        });

        // We query the role.auth relation for the user and correct seeker
        $query->whereHas('roles.auth', function ($query) use ($user, $seeker_id) {
            $query->where(['user_id' => $user->id, 'seeker_id' => $seeker_id]);
        });

        // Run query
        $permission = $query->first();

        // !TODO :: This result should be cached

        // If the above query returned something, then it's a match!
        // Otherwise, might be because the slug doesn't exist, the user is bad, the seeker is bad, etc.
        // In any of those cases, it will be false anyway
        return $permission ? true : false;
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
            Debug::debug("Seekers for permission authorization list requested at: ", $trace);
            Debug::debug("Getting all seekers for user {$user->id} ('{$user->user_name}') on permission '$slug'...");
        }

        // Build the Eloquent query
        // Query the `Auth` Model. We start by getting all the rows specific to this user
        // Strating with this limits the numbers of rows to check the relation on and should be more efficient
        $query = Auth::where('user_id', $user->id);

        // Once we have a list of auth for that user, we only get the auth that contain
        // a role containing the permission slug we are after. This last part is
        // done on the `whereHas` function on the `Auth` relation (ask permission relation throught the `role` relation)
        $query->whereHas('role.permissions', function ($query) use ($slug) {
            $query->where('slug', $slug)
                  ->orWhere('slug', 'like', $slug . '.%');
        });

        // Run query
        $authorizedSeekers = $query->get();

        // !TODO : Cache the result

        // We send the result to the debug
        if ($this->debug) {
            Debug::debug("Autorisation for seekers id {$authorizedSeekers->pluck('seeker_id')}");
        }

        // We loop each result from `Auth` and change it to the seeker collection using the MorphTo relation
        $authorizedSeekers->transform(function ($auth) {
            return $auth->seeker;
        });

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
     * @return Array an array of slugs as string
     */
    public function getPermissionsForSeeker($user, $seeker_id, $seeker_type, $getSeekerClass = true)
    {
        // Display initial debug statement
        if ($this->debug) {
            $trace = array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3), 1);
            Debug::debug("Permissions for seeker authorization list requested at: ", $trace);
            Debug::debug("Getting all permissions for user {$user->id} ('{$user->user_name}') on seekers '$seeker_id' or type `$seeker_type`...");
        }

        // Get full seeker class name
        if ($getSeekerClass) {
            $seeker_type = $this->getSeekerModel($seeker_type);
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

        // Make sure the query returned something
        if (!$auth) {
            return [];
        }

        // Dive down to the permissions collection and get the slugs
        $permissions = $auth->role->permissions->pluck('slug');

        // We have the permissions. We only need to add the inherit one.
        // We loop them all, decomposing each one and adding it to the result
        $result = [];

        foreach ($permissions as $slug) {

            // Decompose the slug
            $decomposedSlug = $this->decomposeSlug($slug);

            // Merge the results and remove duplicated values
            $result = array_merge($result, $decomposedSlug);
            $result = array_unique($result);
        }

        // We send the result to the debug
        if ($this->debug) {
            Debug::debug("Permissions granted: $result");
        }

        // Done !
        return $result;
    }

    /**
     * Decompose a slug formated with dot notation to find all of the
     * inherited permissions
     *
     * @access public
     * @param string $slug
     * @param string $separator (default: ".")
     * @return array Decomposed slugs
     */
    public function decomposeSlug($slug, $separator = ".")
    {
        $decomposedSlug = explode($separator, $slug);
        $result = [];

        foreach ($decomposedSlug as $part) {
            if (empty($result)) {
                $result[] = $part;
            } else {
                $result[] = end($result) . $separator . $part;
            }
        }

        return $result;
    }

    // !TODO
    // public function getPermissions -> Return same as `getPermissionsForSeeker`, but as a multidimentaionnal array of `seeker => [permissions]`
}
