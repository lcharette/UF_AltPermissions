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
    protected $users;
    protected $roles;
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
        $users = collect([
            $fm->create('UserFrosting\Sprinkle\AltPermissions\Database\Models\User', ['user_name' => 'User 1'])
        ]);

        // Create seekers
        $seekers = collect($fm->seed(3, $this->seekerModel));

        // Create roles
        $roles =  collect([
            $fm->create('UserFrosting\Sprinkle\AltPermissions\Database\Models\Role', ['seeker' => $this->seeker, 'name' => "Role 1"])
        ]);

        // Creates permissions
        $permissions =  collect([
            $fm->create('UserFrosting\Sprinkle\AltPermissions\Database\Models\Permission', ['seeker' => $this->seeker, 'name' => "Permission 1", 'slug' => "permission_1"]),
            $fm->create('UserFrosting\Sprinkle\AltPermissions\Database\Models\Permission', ['seeker' => $this->seeker, 'name' => "Permission 2", 'slug' => "permission_2"]),
            $fm->create('UserFrosting\Sprinkle\AltPermissions\Database\Models\Permission', ['seeker' => $this->seeker, 'name' => "Permission 3", 'slug' => "permission_3"])
        ]);

        // Assign users to role and seeker
        $users[0]->seeker($this->seeker)->sync([
            $seekers[0]->id => ['role_id' => $roles[0]->id],
            $seekers[2]->id => ['role_id' => $roles[0]->id]
        ]);

        // Assign Permission to role
        $roles[0]->permissions()->sync([
            $permissions[0]->id,
            $permissions[2]->id
        ]);

        // Add everyone to the testData
        $this->users = $users;
        $this->roles = $roles;
        $this->permissions = $permissions;
        $this->seekers = $seekers;

    }

    public function test_hasPermission()
    {
        /** @var UserFrosting\Sprinkle\AltPermissions\AuthManager $auth */
        $auth = $this->ci->auth;

        // Get the user model
        $user = $this->users[0];

        // We try with the seeker 1.
        $seeker_id = $this->seekers[0]->id;

        // For seeker 1, user should have the first and last permission
        $this->assertTrue($auth->hasPermission($user, $this->permissions[0]->slug, $seeker_id));
        $this->assertFalse($auth->hasPermission($user, $this->permissions[1]->slug, $seeker_id));
        $this->assertTrue($auth->hasPermission($user, $this->permissions[2]->slug, $seeker_id));
    }

    public function test_getSeekersForPermission()
    {
        /** @var UserFrosting\Sprinkle\AltPermissions\AuthManager $auth */
        $auth = $this->ci->auth;

        // Get the user model
        $user = $this->users[0];

        // Get the first permission slug
        $slug = $this->permissions[0]->slug;

        // Ask AuthManager for the list of seekers with that permission
        $result = $auth->getSeekersForPermission($user, $slug);

        // The above returns a seekers collection. We need to pluck those id to form a list
        $resultIds = $result->pluck('seeker_id')->toArray();

        // We should have only the two seekers the user have a role for: 1 & 3
        $expected = [
            $this->seekers[0]->id,
            $this->seekers[2]->id
        ];

        // Test asertion
        $this->assertEquals($expected, $resultIds);


        // With the 3rd permission, it should be the same result
        $slug = $this->permissions[2]->slug;

        // Ask AuthManager for the list of seekers with that permission
        $result = $auth->getSeekersForPermission($user, $slug);
        $resultIds = $result->pluck('seeker_id')->toArray();
        $this->assertEquals($expected, $resultIds);


        // Now we try with the 2nd permission slug. Result should be empty
        $slug = $this->permissions[1]->slug;
        $result = $auth->getSeekersForPermission($user, $slug);
        $resultIds = $result->pluck('seeker_id')->toArray();
        $this->assertEquals([], $resultIds);
    }

    public function test_getPermissionsForSeeker()
    {
        /** @var UserFrosting\Sprinkle\AltPermissions\AuthManager $auth */
        $auth = $this->ci->auth;

        // Get the user model
        $user = $this->users[0];

        // We start with seeker 1
        $seeker_id = $this->seekers[0]->id;

        // Ask AuthManager for the list of permission for that seeker
        $result = $auth->getPermissionsForSeeker($user, $seeker_id, $this->seeker);

        // The above returns a permissions collection. We need to pluck those id to form a list
        $resultIds = $result->pluck('id')->toArray();

        // We should have only the two seekers the user have a role for: 1 & 3
        $expected = [
            $this->permissions[0]->id,
            $this->permissions[2]->id
        ];

        // Test asertion
        $this->assertEquals($expected, $resultIds);


        // With seeker 3, the result should be the same
        $seeker_id = $this->seekers[2]->id;
        $result = $auth->getPermissionsForSeeker($user, $seeker_id, $this->seeker);
        $resultIds = $result->pluck('id')->toArray();
        $this->assertEquals($expected, $resultIds);


        // With seeker 2, should be empty array
        $seeker_id = $this->seekers[1]->id;
        $result = $auth->getPermissionsForSeeker($user, $seeker_id, $this->seeker);
        $resultIds = $result->pluck('id')->toArray();
        $this->assertEquals([], $resultIds);
    }
}