<?php

/*
 * UF AltPermissions Sprinkle
 *
 * @author    Louis Charette
 * @copyright Copyright (c) 2018 Louis Charette
 * @link      https://github.com/lcharette/UF_AltPermissions
 * @license   https://github.com/lcharette/UF_AltPermissions/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle\AltPermissions\Sprunje;

use UserFrosting\Sprinkle\Core\Sprunje\Sprunje;

/**
 * AuthUsersSprunje.
 *
 * Sprunje used to add a new user to the seeker id
 *
 * @author Louis Charette (https://github.com/lcharette)
 */
class AuthUsersSprunje extends Sprunje
{
    protected $name = 'AuthUser';

    protected $sortable = [];
    protected $filterable = [
        'info',
    ];

    /*
     * @var Seeker. The seeker we will be looking for
     */
    protected $seeker_type = '';
    protected $seeker_id = '';

    /*
     * @var where The attribute we'll be doing a where on
     */
    protected $where;

    /**
     * {@inheritdoc}
     */
    public function __construct($classMapper, $options, $seeker_type, $seeker_id)
    {
        $this->seeker_type = $seeker_type;
        $this->seeker_id = $seeker_id;

        // Run parent method
        parent::__construct($classMapper, $options);
    }

    /**
     * {@inheritdoc}
     */
    protected function baseQuery()
    {
        // First, get a list of users in the
        // Lets's find a list of all users for this combinaison so we don't return their names
        $auths = $this->classMapper->staticMethod('altAuth', 'forSeeker', $this->seeker_type, $this->seeker_id)->get();
        $definedUsers = $auths->pluck('user_id')->toArray();

        $query = $this->classMapper->createInstance('user')->whereNotIn('id', $definedUsers);

        return $query;
    }

    /**
     * Filter LIKE the user info.
     *
     * @param Builder $query
     * @param mixed   $value
     *
     * @return Builder
     */
    protected function filterInfo($query, $value)
    {
        // Split value on separator for OR queries
        $values = explode($this->orSeparator, $value);

        return $query->where(function ($query) use ($values) {
            foreach ($values as $value) {
                $query = $query->orLike('first_name', $value)
                                ->orLike('last_name', $value)
                                ->orLike('user_name', $value);
            }

            return $query;
        });
    }

    /**
     * {@inheritdoc}
     */
    protected function applyTransformations($collection)
    {
        $collection = $collection->map(function ($item, $key) {
            //This looks stupid, but Handlebar doesn't have access to magick __get
            $item->avatar = $item->avatar;
        });

        return $collection;
    }
}
