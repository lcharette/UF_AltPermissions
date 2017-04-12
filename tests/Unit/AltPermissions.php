<?php

namespace UserFrosting\Tests\Unit;

use UserFrosting\Tests\TestCase;
use UserFrosting\Tests\DatabaseTransactions;
use League\FactoryMuffin\Faker\Facade as Faker;

use UserFrosting\Sprinkle\AltPermissions\Model\User;
use UserFrosting\Sprinkle\AltPermissions\Model\AltRole;

abstract class AltPermissions extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var The test data we'll in each test
     */
    protected $testData;

    /**
     * @var The seeker that will be tested
     */
    protected $seeker;

    /**
     * @var The seeker model, to test the config works
     */
    protected $seekerModel;

    protected $seekerClass;

    /**
     * setupSeeker function.
     * This function can be used by other class extending this one to perform
     * actions on the seeker before the test are run
     *
     * @access protected
     * @return void
     */
    protected function setupSeeker() {}

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

        // Setup the seeker
        $this->setupSeeker();

        // Create 2 users
        $users = collect($fm->seed(2, 'UserFrosting\Sprinkle\AltPermissions\Model\User'));

        // Create 4 seekers
        $seekers = collect($fm->seed(4, $this->seekerModel));

        // Create 3 roles
        $roles =  collect($fm->seed(3, 'UserFrosting\Sprinkle\AltPermissions\Model\AltRole', ['seeker' => $this->seeker]));

        // Assign them all together
        $users[0]->auth($this->seeker)->sync([
            $seekers[0]->id => ['role_id' => $roles[0]->id],
            $seekers[1]->id => ['role_id' => $roles[0]->id],
            $seekers[2]->id => ['role_id' => $roles[2]->id]
        ]);
        $users[1]->auth($this->seeker)->sync([
            $seekers[0]->id => ['role_id' => $roles[2]->id],
            $seekers[1]->id => ['role_id' => $roles[0]->id],
            $seekers[3]->id => ['role_id' => $roles[1]->id]
        ]);

        // Add everyone to the testData
        $this->testData = (object) [
            'users' => $users->pluck('id'),
            'roles' => $roles->pluck('id'),
            'seekers' => $seekers->pluck('id')
        ];

        $this->seekerClass = new $this->seekerModel;
    }

    public function test_config()
    {
        $config = $this->ci->config;

        // Query config value manually
        $this->assertEquals($this->seekerModel, $config["AltPermissions.seekers"][$this->seeker]);

        // Test dynamic relation using the auth service
        $seekerClass = $this->ci->checkAuthSeeker->getSeekerModel($this->seeker);
        $this->assertEquals($this->seekerModel, $seekerClass);
    }

    /**
     * Test fetching a user's role for a seeker
     */
    public function test_individualUser()
    {
        // Get user n° 1
        $user = User::find($this->testData->users[0]);

        // Get seeker n° 2 id
        $seeker_id = $this->testData->seekers[1];

        // Get user n° 1 role for seeker n° 2
        $role_id = $user->auth($this->seeker)->find($seeker_id)->pivot->role_id;

        // User n° 1 role for seeker n° 2 should be n° 1
        $this->assertEquals($this->testData->roles[0], $role_id);

        // Same test, but on one line. We'll improove on that later ;)
        $this->assertEquals(
            $this->testData->roles[0],
            User::find($this->testData->users[0])->auth($this->seeker)->find($this->testData->seekers[1])->pivot->role_id
        );
    }

    /**
     * Test fetching a seeker's role for a user
     * Plus make sure we can get a role name too
     */
    public function test_individuaSeeker()
    {
        // seeker 2 object
        $seeker = $this->seekerClass->find($this->testData->seekers[1]);

        // User 1 real id
        $user_id = $this->testData->users[0];

        // Role 1 id and name for result comparaison
        $role_id = $this->testData->roles[0];
        $role_name = AltRole::find($role_id)->name;

        // Get the seeker roles, with the user as a pivot where
        $user_role = $seeker->roles()->wherePivot('user_id', $user_id)->first();
        $this->assertEquals($role_id, $user_role->id);
        $this->assertEquals($role_name, $user_role->name);

        // Now get the seeker user. The role id is avaialable in the pivot.
        // This requires the altRole to be queried again. This is not efficient,
        // but we still test it works. Just to be sure
        $user_role = $seeker->users($user_id)->first()->pivot->role_id;
        $user_role = AltRole::find($user_role);
        $this->assertEquals($role_id, $user_role->id);
        $this->assertEquals($role_name, $user_role->name);

        // Now we test the custom pivot.
        //$user_role = $seeker->users($user_id)->first()->role;
        //$this->assertEquals($role_id, $user_role->id);
        //$this->assertEquals($role_name, $user_role->name);
    }

    /**
     * Test the Users => Seekers -> Role relations
     */
    public function test__U_S_R()
    {
        // U    S   R
        // 1    1   1
        // 1    2   1
        // 1    3   3
        // 2    1   3
        // 2    2   1
        // 2    4   2
        $expected_results = collect([
            [
                "user" => $this->testData->users[0],
                "seeker" => $this->testData->seekers[0],
                "role" => $this->testData->roles[0]
            ],
            [
                "user" => $this->testData->users[0],
                "seeker" => $this->testData->seekers[1],
                "role" => $this->testData->roles[0]
            ],
            [
                "user" => $this->testData->users[0],
                "seeker" => $this->testData->seekers[2],
                "role" => $this->testData->roles[2]
            ],
            [
                "user" => $this->testData->users[1],
                "seeker" => $this->testData->seekers[0],
                "role" => $this->testData->roles[2]
            ],
            [
                "user" => $this->testData->users[1],
                "seeker" => $this->testData->seekers[1],
                "role" => $this->testData->roles[0]
            ],
            [
                "user" => $this->testData->users[1],
                "seeker" => $this->testData->seekers[3],
                "role" => $this->testData->roles[1]
            ]
        ]);

        $results = collect([]);

        $users = User::find($this->testData->users->toArray());
        foreach ($users as $user) {

            $seekers = $user->auth($this->seeker)->get();
            foreach ($seekers as $seeker) {

                //!TODO -> Custom pivot
                $role_id = $seeker->pivot->role_id;
                $role = AltRole::find($role_id);

                $collect = [
                    "user" => $user->id,
                    "seeker" => $seeker->id,
                    "role" => $role->id
                ];
                $results->push($collect);
            }
        }

        $this->assertEquals($expected_results, $results);
    }

    /**
     * Test the Users => Roles => Seekers relations
     */
    public function test__U_R_S()
    {
        // U    R   S
        // 1    1   1,2
        // 1    3   3
        // 2    1   2
        // 2    2   7
        // 2    3   1
        $expected_results = collect([
            [
                "user" => $this->testData->users[0],
                "role" => $this->testData->roles[0],
                "seeker" => $this->testData->seekers[0].",".$this->testData->seekers[1]
            ],
            [
                "user" => $this->testData->users[0],
                "role" => $this->testData->roles[2],
                "seeker" => $this->testData->seekers[2]
            ],
            [
                "user" => $this->testData->users[1],
                "role" => $this->testData->roles[2],
                "seeker" => $this->testData->seekers[0]
            ],
            [
                "user" => $this->testData->users[1],
                "role" => $this->testData->roles[0],
                "seeker" => $this->testData->seekers[1]
            ],
            [
                "user" => $this->testData->users[1],
                "role" => $this->testData->roles[1],
                "seeker" => $this->testData->seekers[3]
            ]
        ]);

        $results = collect([]);

        $users = User::find($this->testData->users->toArray());
        foreach ($users as $user) {

            $roles = $user->altRole->groupBy('id');
            foreach ($roles as $role_collection) {

                $role = $role_collection->first();

                $seekers = collect([]);
                foreach($role_collection as $i) {

                    $seeker_id = $i->pivot->seeker_id;
                    $seeker = $this->seekerClass->find($seeker_id);
                    $seekers->push($seeker);
                }

                $collect = [
                    "user" => $user->id,
                    "role" => $role->id,
                    "seeker" => $seekers->implode("id", ",")
                ];
                $results->push($collect);
            }
        }

        $this->assertEquals($expected_results, $results);
    }

    /**
     * Test the Roles => Users => Sekers relations
     */
    public function test__R_U_S()
    {
        // R    U   S
        // 1    1   1,2
        // 1    2   2
        // 2    2   4
        // 3    1   3
        // 3    2   1
        $expected_results = collect([
            [
                "role" => $this->testData->roles[0],
                "user" => $this->testData->users[0],
                "seeker" => $this->testData->seekers[0].",".$this->testData->seekers[1]
            ],
            [
                "role" => $this->testData->roles[0],
                "user" => $this->testData->users[1],
                "seeker" => $this->testData->seekers[1]
            ],
            [
                "role" => $this->testData->roles[1],
                "user" => $this->testData->users[1],
                "seeker" => $this->testData->seekers[3]
            ],
            [
                "role" => $this->testData->roles[2],
                "user" => $this->testData->users[0],
                "seeker" => $this->testData->seekers[2]
            ],
            [
                "role" => $this->testData->roles[2],
                "user" => $this->testData->users[1],
                "seeker" => $this->testData->seekers[0]
            ]
        ]);

        $results = collect([]);

        $roles = AltRole::find($this->testData->roles->toArray());
        foreach ($roles as $role) {

            $users = $role->users->groupBy('id');
            foreach ($users as $user_collection) {

                $user = $user_collection->first();

                $seekers = collect([]);
                foreach($user_collection as $i) {

                    $seeker_id = $i->pivot->seeker_id;
                    $seeker = $this->seekerClass->find($seeker_id);
                    $seekers->push($seeker);
                }

                $collect = [
                    "role" => $role->id,
                    "user" => $user->id,
                    "seeker" => $seekers->implode("id", ",")
                ];
                $results->push($collect);
            }
        }

        $this->assertEquals($expected_results, $results);
    }

    /**
     * Test the Roles => Seekers => Users relations
     */
    public function test__R_S_U()
    {
        // R    S   U
        // 1    1   1
        // 1    2   1,2
        // 2    4   2
        // 3    1   2
        // 3    3   1
        $expected_results = collect([
            [
                "role" => $this->testData->roles[0],
                "seeker" => $this->testData->seekers[0],
                "user" => $this->testData->users[0]
            ],
            [
                "role" => $this->testData->roles[0],
                "seeker" => $this->testData->seekers[1],
                "user" => $this->testData->users[0].",".$this->testData->users[1]
            ],
            [
                "role" => $this->testData->roles[1],
                "seeker" => $this->testData->seekers[3],
                "user" => $this->testData->users[1]
            ],
            [
                "role" => $this->testData->roles[2],
                "seeker" => $this->testData->seekers[2],
                "user" => $this->testData->users[0]
            ],
            [
                "role" => $this->testData->roles[2],
                "seeker" => $this->testData->seekers[0],
                "user" => $this->testData->users[1]
            ]
        ]);

        $results = collect([]);

        $roles = AltRole::find($this->testData->roles->toArray());
        foreach ($roles as $role) {

            $seekers = $role->auth($this->seeker)->get()->groupBy('id');
            foreach ($seekers as $seeker_collection) {

                $seeker = $seeker_collection->first();

                $users = collect([]);
                foreach($seeker_collection as $i) {

                    $user_id = $i->pivot->user_id;
                    $user = User::find($user_id);
                    $users->push($user);
                }

                $collect = [
                    "role" => $role->id,
                    "seeker" => $seeker->id,
                    "user" => $users->implode("id", ",")
                ];
                $results->push($collect);
            }
        }

        $this->assertEquals($expected_results, $results);
    }

    /**
     * Test the Seekers => Roles => Users relations
     */
    public function test__S_R_U()
    {
        // S    R   U
        // 1    1   1
        // 1    3   2
        // 2    1   1,2
        // 3    3   1
        // 4    2   2
        $expected_results = collect([
            [
                "seeker" => $this->testData->seekers[0],
                "role" => $this->testData->roles[0],
                "user" => $this->testData->users[0]
            ],
            [
                "seeker" => $this->testData->seekers[0],
                "role" => $this->testData->roles[2],
                "user" => $this->testData->users[1]
            ],
            [
                "seeker" => $this->testData->seekers[1],
                "role" => $this->testData->roles[0],
                "user" => $this->testData->users[0].",".$this->testData->users[1]
            ],
            [
                "seeker" => $this->testData->seekers[2],
                "role" => $this->testData->roles[2],
                "user" => $this->testData->users[0]
            ],
            [
                "seeker" => $this->testData->seekers[3],
                "role" => $this->testData->roles[1],
                "user" => $this->testData->users[1]
            ]
        ]);

        $results = collect([]);

        $seekers = $this->seekerClass->find($this->testData->seekers->toArray());
        foreach ($seekers as $seeker) {

            $roles = $seeker->roles->groupBy('id');
            foreach ($roles as $role_collection) {

                $role = $role_collection->first();

                $users = collect([]);
                foreach($role_collection as $i) {

                    $user_id = $i->pivot->user_id;
                    $user = User::find($user_id);
                    $users->push($user);
                }

                $collect = [
                    "seeker" => $seeker->id,
                    "role" => $role->id,
                    "user" => $users->implode("id", ",")
                ];
                $results->push($collect);
            }
        }

        $this->assertEquals($expected_results, $results);
    }

    /**
     * Test the Seekers => Users -> Role relations
     */
    public function test__S_U_R()
    {
        // S    U   R
        // 1    1   1
        // 1    2   3
        // 2    1   1
        // 2    2   1
        // 3    1   3
        // 4    2   2
        $expected_results = collect([
            [
                "seeker" => $this->testData->seekers[0],
                "user" => $this->testData->users[0],
                "role" => $this->testData->roles[0]
            ],
            [
                "seeker" => $this->testData->seekers[0],
                "user" => $this->testData->users[1],
                "role" => $this->testData->roles[2]
            ],
            [
                "seeker" => $this->testData->seekers[1],
                "user" => $this->testData->users[0],
                "role" => $this->testData->roles[0]
            ],
            [
                "seeker" => $this->testData->seekers[1],
                "user" => $this->testData->users[1],
                "role" => $this->testData->roles[0]
            ],
            [
                "seeker" => $this->testData->seekers[2],
                "user" => $this->testData->users[0],
                "role" => $this->testData->roles[2]
            ],
            [
                "seeker" => $this->testData->seekers[3],
                "user" => $this->testData->users[1],
                "role" => $this->testData->roles[1]
            ]
        ]);

        $results = collect([]);

        $seekers = $this->seekerClass->find($this->testData->seekers->toArray());
        foreach ($seekers as $seeker) {

            $users = $seeker->users;
            foreach ($users as $user) {

                //!TODO -> Custom pivot
                $role_id = $user->pivot->role_id;
                $user->role = AltRole::find($role_id);

                $collect = [
                    "seeker" => $seeker->id,
                    "role" => $user->role->id,
                    "user" => $user->id
                ];
                $results->push($collect);
            }
        }

        $this->assertEquals($expected_results, $results);
    }
}