<?php

namespace UserFrosting\Tests\Unit;

use UserFrosting\Tests\TestCase;
use UserFrosting\Tests\DatabaseTransactions;

class AuthManagerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var The seeker that will be tested
     */
    protected $seeker = "foo";

    /**
     * @var The seeker model
     */
    protected $seekerModel = "UserFrosting\Tests\Models\Foo";

    /**
     * @var The test data we'll in each test
     */
    protected $user;
    protected $role;
    protected $permissions;
    protected $seekers;

    /**
     * @var Bool. Enabled/Disable verbose debugging
     */
    protected $debug = true;

    /**
     * setUp function.
     * Load the model factories
     */
    protected function setUp()
    {
        // Setup parent first to get access to the container
        parent::setUp();

        // @var League\FactoryMuffin\FactoryMuffin
        $fm = $this->ci->factory;

        /* Create the test permission structure
         *
         * Role 1:
         *   - Permission 1 => On
         *   - Permission 2 => Off
         *   - Permission 3 => On
         *
         * User 1 :
         *   - Seeker 1 -> Role 1
         *   - Seeker 2 -> {No Role}
         *   - Seeker 3 -> Role 1
         */

        // Create users
        $this->user = $fm->create('UserFrosting\Sprinkle\AltPermissions\Database\Models\User', ['user_name' => 'User 1']);

        // Create seekers
        $this->seekers = collect($fm->seed(3, $this->seekerModel));

        // Create roles
        $this->role = $fm->create('UserFrosting\Sprinkle\AltPermissions\Database\Models\Role', ['seeker' => $this->seeker, 'name' => "Role 1"]);

        // Creates permissions
        $this->permissions = collect([
            $fm->create('UserFrosting\Sprinkle\AltPermissions\Database\Models\Permission', ['seeker' => $this->seeker, 'name' => "Permission", 'slug' => "permission"]),
            $fm->create('UserFrosting\Sprinkle\AltPermissions\Database\Models\Permission', ['seeker' => $this->seeker, 'name' => "Permission Test", 'slug' => "permission.test"]),
            $fm->create('UserFrosting\Sprinkle\AltPermissions\Database\Models\Permission', ['seeker' => $this->seeker, 'name' => "Permission Foo", 'slug' => "permission.foo"]),
            $fm->create('UserFrosting\Sprinkle\AltPermissions\Database\Models\Permission', ['seeker' => $this->seeker, 'name' => "Permission Foo Bar", 'slug' => "permission.foo.bar"]),
            $fm->create('UserFrosting\Sprinkle\AltPermissions\Database\Models\Permission', ['seeker' => $this->seeker, 'name' => "PermissionFooBar", 'slug' => "permissionFooBar"]),
            $fm->create('UserFrosting\Sprinkle\AltPermissions\Database\Models\Permission', ['seeker' => $this->seeker, 'name' => "PermissionFoo", 'slug' => "permissionFoo"]),
        ]);

        // Assign users to role and seeker
        $this->user->seeker($this->seeker)->sync([
            $this->seekers[0]->id => ['role_id' => $this->role->id],
            $this->seekers[2]->id => ['role_id' => $this->role->id]
        ]);

        // Assign Permission to role
        $this->role->permissions()->sync([
            $this->permissions[0]->id,
            $this->permissions[3]->id,
            $this->permissions[4]->id
        ]);
    }

    /**
     * Test the hasPermission method from AuthManager.
     */
    public function test_hasPermission()
    {
        /** @var UserFrosting\Sprinkle\AltPermissions\AuthManager $auth */
        $auth = $this->ci->auth;

        // We try with the seeker 1.
        $seeker_id = $this->seekers[0]->id;

        // For seeker 1, user should have : permission, permission.foo., permission.foo.bar, permissionthat
        $this->assertTrue($auth->hasPermission($this->user, "permission", $seeker_id)); // Direct true
        $this->assertFalse($auth->hasPermission($this->user, "permission.test", $seeker_id)); // False
        $this->assertTrue($auth->hasPermission($this->user, "permission.foo", $seeker_id)); // Inherit from `permission.foo.bar`
        $this->assertTrue($auth->hasPermission($this->user, "permission.foo.bar", $seeker_id)); // Direct true
        $this->assertTrue($auth->hasPermission($this->user, "permissionFooBar", $seeker_id)); // Direct true
        $this->assertFalse($auth->hasPermission($this->user, "permissionFoo", $seeker_id)); // False
    }

    /**
     * Test the getSeekersForPermission method from AuthManager.
     */
    public function test_getSeekersForPermission()
    {
        /** @var UserFrosting\Sprinkle\AltPermissions\AuthManager $auth */
        $auth = $this->ci->auth;

        // Get the first permission slug (permission)
        $slug = $this->permissions[0]->slug;

        // Ask AuthManager for the list of seekers with that permission
        $result = $auth->getSeekersForPermission($this->user, $slug);

        // The above returns a seekers collection. We need to pluck those id to form a list
        $resultIds = $result->pluck('id')->toArray();

        // We should have only the two seekers the user have a role for: 1 & 3
        $expected = [
            $this->seekers[0]->id,
            $this->seekers[2]->id
        ];

        // Test asertion
        $this->assertEquals($expected, $resultIds);


        // With the 3rd permission (permission.foo), it should be the same result
        $slug = $this->permissions[2]->slug;

        // Ask AuthManager for the list of seekers with that permission
        $result = $auth->getSeekersForPermission($this->user, $slug);
        $resultIds = $result->pluck('id')->toArray();
        $this->assertEquals($expected, $resultIds);

        // Same for fourth (permission.foo.bar)
        $slug = $this->permissions[3]->slug;
        $result = $auth->getSeekersForPermission($this->user, $slug);
        $resultIds = $result->pluck('id')->toArray();
        $this->assertEquals($expected, $resultIds);

        // And same for fifth (permisionthat)
        $slug = $this->permissions[4]->slug;
        $result = $auth->getSeekersForPermission($this->user, $slug);
        $resultIds = $result->pluck('id')->toArray();
        $this->assertEquals($expected, $resultIds);

        // Now we try with the 2nd permission slug (permission.test). Result should be empty
        $slug = $this->permissions[1]->slug;
        $result = $auth->getSeekersForPermission($this->user, $slug);
        $resultIds = $result->pluck('id')->toArray();
        $this->assertEquals([], $resultIds);

        // Same for the last permission (permissionthis)
        $slug = $this->permissions[5]->slug;
        $result = $auth->getSeekersForPermission($this->user, $slug);
        $resultIds = $result->pluck('id')->toArray();
        $this->assertEquals([], $resultIds);
    }

    /**
     * Test the getPermissionsForSeeker method from AuthManager.
     */
    public function test_getPermissionsForSeeker()
    {
        /** @var UserFrosting\Sprinkle\AltPermissions\AuthManager $auth */
        $auth = $this->ci->auth;

        // We start with seeker 1
        $seeker_id = $this->seekers[0]->id;

        // Ask AuthManager for the list of permission for that seeker
        $result = $auth->getPermissionsForSeeker($this->user, $seeker_id, $this->seeker);

        // The above returns a permissions collection. We need to pluck those id to form a list
        $resultIds = $result->pluck('slug')->toArray();

        // We should have only 4 permissions slug
        $expected = [
            $this->permissions[0]->slug, // permission
            $this->permissions[2]->slug, // permission.foo (inherited)
            $this->permissions[3]->slug, // permission.foo.bar
            $this->permissions[4]->slug  // permissionthat
        ];

        // Test asertion
        $this->assertEquals($expected, $resultIds);


        // With seeker 3, the result should be the same
        $seeker_id = $this->seekers[2]->id;
        $result = $auth->getPermissionsForSeeker($this->user, $seeker_id, $this->seeker);
        $resultIds = $result->pluck('id')->toArray();
        $this->assertEquals($expected, $resultIds);


        // With seeker 2, should be empty array
        $seeker_id = $this->seekers[1]->id;
        $result = $auth->getPermissionsForSeeker($this->user, $seeker_id, $this->seeker);
        $resultIds = $result->pluck('id')->toArray();
        $this->assertEquals([], $resultIds);
    }
}