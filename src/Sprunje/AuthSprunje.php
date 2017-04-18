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
class AuthSprunje extends Sprunje
{
    protected $name = 'rolesAuth';

    /* Nb.: Since the language key is stored in the db, the db can't be
       used for sorting and filtering at this time */
    protected $sortable = [];
    protected $filterable = [];

    /*
     * @var Seeker. The seeker we will be looking for
     */
    protected $seeker = "";

    /*
     * @var where The attribute we'll be doing a where on
     */
    protected $where;

    /**
     * {@inheritDoc}
     */
    public function __construct($classMapper, $options, $seeker, $where = [])
    {
        $this->seeker = $seeker;
        $this->where = $where;

        // Run parent method
        parent::__construct($classMapper, $options);
    }

    /**
     * {@inheritDoc}
     */
    protected function baseQuery()
    {
        $query = $this->classMapper->createInstance('altAuth')                  // Get Auth model
                                   ->forSeeker($this->seeker)                   // With the seeker key
                                   ->with(['user', 'role', 'seeker']);          // Eager load the relations for Handlebar

        // Apply where contraints if any
        if (!empty($this->where)) {
            $query = $query->where($this->where);
        }

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
            /*$item->uri = [
                'delete' => $role->getRoute('api.auth.delete'),
                'edit'   => $role->getRoute('modal.auth.edit'),
            ];*/

            return $role;
        });

        return $collection;
    }
}
