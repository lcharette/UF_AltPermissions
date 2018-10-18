<?php

/*
 * UF AltPermissions
 *
 * @link https://github.com/lcharette/UF-AltPermissions
 *
 * @copyright Copyright (c) 2016 Louis Charette
 * @license https://github.com/lcharette/UF-AltPermissions/blob/master/licenses/UserFrosting.md (MIT License)
 */

return [

    'ALT_ROLE' => [
        'PAGE_DESCRIPTION'  => 'A listing of the roles for the <em>{{seeker}}</em> seeker.  Provides management tools for editing and deleting roles for this seeker.',
        'PAGE_TITLE'        => 'Roles for {{seeker}}',

        'USERS' => 'Role users',
    ],

    'AUTH' => [
        'BAD_SEEKER' => 'The selected role seeker is invalid',
        'NOT_FOUND'  => "The selected role doesn't exist",

        'CREATED' => '<strong>{{user_name}}</strong> successfully added with role <strong>{{role_name}}</strong>',
        'UPDATED' => 'Role <strong>{{role_name}}</strong> successfully defined for <strong>{{user_name}}</strong>',
        'DELETED' => 'Role <strong>{{role_name}}</strong> successfully removed for <strong>{{user_name}}</strong>',

        'ADD_USER'      => 'Add {{&USER}}',
        'SELECT_USER'   => 'Select {{&USER}}',
        'USER_HAS_ROLE' => 'The selected {{&USER}} already has a role defined for this seeker',
    ],
];
