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
use UserFrosting\Sprinkle\AltPermissions\Model\Auth;

/**
 * AuthSprunje
 *
 * Sprunje displaying the
 *
 * @author Louis Charette (https://github.com/lcharette)
 */
class AuthSprunje extends Sprunje
{
    protected $name = 'rolesAuth';

    /* Nb.: Since the language key is stored in the db, the db can't be
       used for sorting and filtering at this time */
    protected $sortable = [
        'user'
    ];
    protected $filterable = [
        'user'
    ];

    /**
     * @var bool Keep track of whether the users table has already been joined on the query.
     */
    protected $joinedUsers = false;

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
            $item->uri = [
                'delete' => $item->getRoute('api.auth.delete'),
                'edit'   => $item->getRoute('modal.auth.edit'),
            ];
        });

        return $collection;
    }

    /**
     * Filter LIKE the user info.
     *
     * @param Builder $query
     * @param mixed $value
     * @return Builder
     */
    protected function filterUser($query, $value)
    {
        if (!$this->joinedUsers) {
            $query = $query->joinUser();
        }

        $this->joinedUsers = true;

        // Split value on separator for OR queries
        $values = explode($this->orSeparator, $value);
        return $query->where(function ($query) use ($values) {
            foreach ($values as $value) {
                $query = $query->orLike('users.first_name', $value)
                                ->orLike('users.last_name', $value)
                                ->orLike('users.user_name', $value);
            }
            return $query;
        });
    }

    /**
     * Sort based on user last name.
     *
     * @param Builder $query
     * @param string $direction
     * @return Builder
     */
    protected function sortUser($query, $direction)
    {
        if (!$this->joinedUsers) {
            $query = $query->joinUser();
        }

        $this->joinedUsers = true;

        return $query->orderBy('users.first_name', $direction);
    }
}
