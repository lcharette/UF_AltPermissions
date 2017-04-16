<?php
 /**
 * UF AltPermissions
 *
 * @link      https://github.com/lcharette/UF-AltPermissions
 * @copyright Copyright (c) 2016 Louis Charette
 * @license   https://github.com/lcharette/UF-AltPermissions/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace UserFrosting\Sprinkle\AltPermissions\Sprunje;

use UserFrosting\Sprinkle\Core\Sprunje\Sprunje;
use UserFrosting\Sprinkle\Core\Facades\Debug;

use UserFrosting\Sprinkle\AltPermissions\AltRoleUsers;

/**
 * RoleSprunje
 *
 * Implements Sprunje for the roles API.
 *
 * @author Louis Charette (https://github.com/lcharette)
 */
class RoleAuthSprunje extends Sprunje
{
    protected $name = 'rolesAuth';

    /* Nb.: Since the language key is stored in the db, the db can't be
       used for sorting and filtering at this time */
    protected $sortable = [];
    protected $filterable = [];


    protected $seeker = "";
    protected $auth_options = [];

    /**
     * {@inheritDoc}
     */
    public function __construct($classMapper, $options, $seeker, $auth_options = [])
    {
        $this->seeker = $seeker;
        $this->auth_options = array_merge([
            "seeker_id" => 0,
            "role_id" => 0,
            "user_id" => 0
        ], $auth_options);

        // Run parent method
        parent::__construct($classMapper, $options);
    }

    /**
     * {@inheritDoc}
     */
    protected function baseQuery()
    {
        //Debug::debug(print_r($this->auth_options, true));
        $query = $this->classMapper->createInstance('altAuth')                  // Get Auth model
                                   ->forSeeker($this->seeker)                   // With the seeker key
                                   ->with(['user', 'role', 'seeker'])           // Eager load the relations for Handlebar
                                   ->where(array_filter($this->auth_options));  // Apply where contraints, using only non false (0) key
        return $query;
    }

    /**
     * {@inheritDoc}
     */
    protected function applyTransformations($collection)
    {
        $collection = $collection->map(function ($item, $key) {

            // Replace the name and description for the translated version of the role name and description
            // Since we are loading into Handlebar and not Twig, the GetMutator can't be used in template
            $item->role->name = $item->role->getLocaleName();
            $item->role->description = $item->role->getLocaleDescription();

            // Add routes
            /*$role->uri = [
                'view'   => $role->getRoute('alt_uri_roles.view'),
                'delete' => $role->getRoute('api.roles.delete'),
                'edit'   => $role->getRoute('modal.roles.edit'),
                'permissions' => $role->getRoute('modal.roles.permissions')
            ];*/

            return $role;
        });

        return $collection;
    }
}
