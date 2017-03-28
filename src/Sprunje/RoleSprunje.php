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

/**
 * RoleSprunje
 *
 * Implements Sprunje for the roles API.
 *
 * @author Louis Charette (https://github.com/lcharette)
 */
class RoleSprunje extends Sprunje
{
    protected $name = 'roles';

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
        return $this->classMapper->createInstance('altRole')->forSeeker($this->seeker);
    }

    /**
     * {@inheritDoc}
     */
    protected function applyTransformations($collection)
    {
        $collection = $collection->map(function ($item, $key) {

            // Replace the name and description for the translated version
            $item->name = $item->getLocaleName();
            $item->description = $item->getLocaleDescription();

            // Route
            $item->uri_delete = $item->getRoute('api.roles.delete');
            $item->uri_edit_form = $item->getRoute('api.roles.edit.form');

            return $item;
        });

        return $collection;
    }
}
