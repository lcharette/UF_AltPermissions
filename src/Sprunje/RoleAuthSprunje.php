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

    /**
     * {@inheritDoc}
     */
    public function __construct($classMapper, $options, $seeker)
    {
        $this->seeker = $seeker;
        parent::__construct($classMapper, $options);
    }

    /**
     * {@inheritDoc}
     */
    protected function baseQuery()
    {
        $query = $this->classMapper->createInstance('altRole')->forSeeker($this->seeker)->with('users');

        //Debug::debug(print_r($query->get(), true));
        return $query;
    }

    /**
     * {@inheritDoc}
     */
    protected function applyTransformations($collection)
    {
        /*$collection = $collection->map(function ($item, $key) {

            // Replace the name and description for the translated version
            $item->name = $item->getLocaleName();
            $item->description = $item->getLocaleDescription();

            // Routes
            $item->uri = [
                'view'   => $item->getRoute('alt_uri_roles.view'),
                'delete' => $item->getRoute('api.roles.delete'),
                'edit'   => $item->getRoute('modal.roles.edit'),
                'permissions' => $item->getRoute('modal.roles.permissions')
            ];

            return $item;
        });*/

        return $collection;
    }
}
