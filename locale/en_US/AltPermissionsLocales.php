<?php

 /**
 * UF AltPermissions
 *
 * en_US
 *
 * US English message token translations for the 'AltPermissions' sprinkle.
 *
 * @link      https://github.com/lcharette/UF-AltPermissions
 * @copyright Copyright (c) 2016 Louis Charette
 * @license   https://github.com/lcharette/UF-AltPermissions/blob/master/licenses/UserFrosting.md (MIT License)
 */

return [

    "ALT_ROLE" => [
        "PAGE_DESCRIPTION"  => "A listing of the roles for the <em>{{seeker}}</em> seeker.  Provides management tools for editing and deleting roles for this seeker.",
        "PAGE_TITLE" => "Roles for {{seeker}}",

        "USERS" => "Role users",

        "DEFAULT" => [
            "@TRANSLATION" => "Default role",
            "CONFIRM" => "Are you sure you want to set this role as the default role? User without a role will inherit this role.",
            "CONFIRM_UNSET" => "Are you sure you want to remove the default role status from the selected role? User without a role will have no permissions (all permissions off).",
            "UPDATED" => "Role <strong>{{role_name}}</strong> set as default for the <em>{{seeker}}</em> seeker",
            "UPDATED_UNSET" => "Role <strong>{{role_name}}</strong> unset as default for the <em>{{seeker}}</em> seeker",
            "SET" => "Set as default role",
            "UNSET" => "Remove as the default role"
        ]
    ],

    "AUTH" => [
        "BAD_SEEKER" => "The selected role seeker is invalid",
        "NOT_FOUND" => "The selected role doesn't exist",

        "CREATED" => "<strong>{{user_name}}</strong> successfully added with role <strong>{{role_name}}</strong>",
        "UPDATED" => "Role <strong>{{role_name}}</strong> successfully defined for <strong>{{user_name}}</strong>",
        "DELETED" => "Role <strong>{{role_name}}</strong> successfully removed for <strong>{{user_name}}</strong>",

        "ADD_USER" => "Add {{&USER}}",
        "SELECT_USER" => "Select {{&USER}}",
        "USER_HAS_ROLE" => "The selected {{&USER}} already has a role defined for this seeker"
    ]
];
