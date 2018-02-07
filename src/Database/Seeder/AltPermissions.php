<?php
/**
*    UF AltPermissions
*
*    @link      https://github.com/lcharette/UF-AltPermissions
*    @copyright Copyright (c) 2016 Louis Charette
*    @license   https://github.com/lcharette/UF-AltPermissions/blob/master/licenses/UserFrosting.md (MIT License)
*/
namespace UserFrosting\Sprinkle\AltPermissions\Database\Seeder;

use UserFrosting\Sprinkle\Core\Database\Seeder\Seeder;
use UserFrosting\Sprinkle\Account\Database\Models\Permission;
use UserFrosting\Sprinkle\Account\Database\Models\Role;

/**
 *    Seeder for AltPermissions core permissions
 */
class AltPermissions extends Seeder
{
    /**
     *    @inheritDoc
     */
    public function run()
    {
        // Add default permissions
        $permissions = [
            'alt_create_role' => new Permission([
                'slug' => 'alt_create_role',
                'name' => 'Create seeker role',
                'conditions' => 'always()',
                'description' => 'Create a new role for seeker permissions.'
            ]),
            'alt_update_role_field' => new Permission([
                'slug' => 'alt_update_role_field',
                'name' => 'Update seeker role field',
                'conditions' => 'always()',
                'description' => 'Update the role for a seeker.'
            ]),
            'alt_delete_role' => new Permission([
                'slug' => 'alt_delete_role',
                'name' => 'Delete seeker role',
                'conditions' => 'always()',
                'description' => 'Delete a role for seeker permissions.'
            ]),
            'alt_view_role_field' => new Permission([
                'slug' => 'alt_view_role_field',
                'name' => 'View seeker role field',
                'conditions' => 'always()',
                'description' => 'View field for a seeker role.'
            ]),
            'alt_uri_role' => new Permission([
                'slug' => 'alt_uri_role',
                'name' => 'View seeker role page',
                'conditions' => 'always()',
                'description' => 'View seeker role page.'
            ]),
        ];

        // Get site-admin role
        $roleSiteAdmin = Role::where('slug', 'site-admin')->first();

        // Create each permissions
        foreach ($permissions as $slug => $permission) {
            // Make sure it doesn't already exist
            if (!Permission::where('slug', $slug)->first()) {
                $permission->save();

                if ($roleSiteAdmin) {
                    $roleSiteAdmin->permissions()->attach($permission->id);
                }
            }
        }
    }
}
