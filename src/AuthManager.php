<?php
 /**
 * UF AltPermissions
 *
 * @link      https://github.com/lcharette/UF-AltPermissions
 * @copyright Copyright (c) 2016 Louis Charette
 * @license   https://github.com/lcharette/UF-AltPermissions/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace UserFrosting\Sprinkle\AltPermissions;

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
     * Create a new AuthorizationManager object.
     *
     * @param ContainerInterface $ci The global container object, which holds all your services.
     */
    public function __construct($ci)
    {
        $this->ci = $ci;
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
    public function check($user, $slug, $seeker_id)
    {
        $debug = $this->ci->config['debug.auth'];

        if ($debug) {
            $trace = array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3), 1);
            $this->ci->authLogger->debug("Authorization check requested at: ", $trace);
            $this->ci->authLogger->debug("Checking authorization for user {$user->id} ('{$user->user_name}') on permission '$slug' and seeker id '$seeker_id'...");
        }

        // We find the permission related to that slug
        /*$permission = $this->cache->rememberForever("auth.permissions.$slug", function () use ($slug) {
            return Permission::with('roles')->where('slug', $slug)->first();
        });*/

        // Super mighty query
        // !TODO :: This result should be cached
        $permission = Permission::whereHas('roles.auth', function ($query) use ($user, $seeker_id) {
            $query->where(['user_id' => $user->id, 'seeker_id' => $seeker_id]);
        })->where('slug', $slug)->first();

        // If the above query returned something, then it's a match!
        // Otherwise, might be because the slug doesn't exist, the user is bad, the seeker is bad, etc.Just ch
        // In any of those cases, it will be false anyway
        return ($permission ? true : false);

    }
}
