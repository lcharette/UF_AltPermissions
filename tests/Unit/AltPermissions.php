<?php

namespace UserFrosting\Tests\Unit;

use UserFrosting\Tests\TestCase;
use UserFrosting\Tests\DatabaseTransactions;

use UserFrosting\Sprinkle\AltPermissions\Database\Models\User;
use UserFrosting\Sprinkle\AltPermissions\Database\Models\Role;

abstract class AltPermissions extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var The test data we'll in each test
     */
    protected $users;
    protected $roles;
    protected $seekers;

    /**
     * @var The seeker that will be tested
     */
    protected $seeker;

    /**
     * @var The seeker model, to test the config works
     */
    protected $seekerModel;

    /**
     * @var The seeker class. So we don't have to call a new one each time
     */
    protected $seekerClass;

    /**
     * @var Bool. Enabled/Disable verbose debugging
     */
    protected $debug = false;

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

        // Create 2 users
        $users = collect([
            $fm->create('UserFrosting\Sprinkle\AltPermissions\Database\Models\User', ['user_name' => 'User 1']),
            $fm->create('UserFrosting\Sprinkle\AltPermissions\Database\Models\User', ['user_name' => 'User 2'])
        ]);

        // Create 4 seekers
        $seekers = collect($fm->seed(4, $this->seekerModel));

        // Create 3 roles
        $roles =  collect([
            $fm->create('UserFrosting\Sprinkle\AltPermissions\Database\Models\Role', ['seeker' => $this->seeker, 'name' => "Role 1"]),
            $fm->create('UserFrosting\Sprinkle\AltPermissions\Database\Models\Role', ['seeker' => $this->seeker, 'name' => "Role 2"]),
            $fm->create('UserFrosting\Sprinkle\AltPermissions\Database\Models\Role', ['seeker' => $this->seeker, 'name' => "Role 3"])
        ]);

        // Assign them all together
        $users[0]->seeker($this->seeker)->sync([
            $seekers[0]->id => ['role_id' => $roles[0]->id],
            $seekers[1]->id => ['role_id' => $roles[0]->id],
            $seekers[2]->id => ['role_id' => $roles[2]->id]
        ]);
        $users[1]->seeker($this->seeker)->sync([
            $seekers[0]->id => ['role_id' => $roles[2]->id],
            $seekers[1]->id => ['role_id' => $roles[0]->id],
            $seekers[3]->id => ['role_id' => $roles[1]->id]
        ]);

        // Add everyone to the testData
        $this->users = (object) $users->pluck('id');
        $this->roles = (object) $roles->pluck('id');
        $this->seekers = (object) $seekers->pluck('id');

        // Se we don't have to call "new" each time
        $this->seekerClass = new $this->seekerModel;
    }

    /**
     * Test fetching a seeker config value (getSeekerModel function)
     */
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
        $user = User::find($this->users[0]);

        // Get seeker n° 2 id
        $seeker_id = $this->seekers[2];

        // Get user n° 1 auth data for seeker n° 2
        $auth = $user->auth($this->seeker)->where('seeker_id', $seeker_id)->first();

        // User n° 1 role for seeker n° 2 should be n° 1
        $this->assertEquals($this->roles[2], $auth->role->id);


        // Same test, but on one line. We'll improove on that later ;)
        $this->assertEquals(
            $this->roles[2],
            User::find($this->users[0])->auth($this->seeker)->where('seeker_id', $seeker_id)->first()->role->id
        );


        // The one above is too long for nothing...
        //$role = $user->roleForSeeker($this->seeker, $seeker_id)
        $this->assertEquals(
            $this->roles[2],
            User::find($this->users[0])->roleForSeeker($this->seeker, $seeker_id)->id
        );
    }

    /**
     * Test fetching a seeker's role for a user
     * Plus make sure we can get a role name too
     */
    public function test_individualSeeker()
    {
        // seeker 2 object
        $seeker = $this->seekerClass->find($this->seekers[1]);

        // User 1 real id
        $user_id = $this->users[0];

        // Role 1 id and name for result comparaison
        $role_id = $this->roles[0];
        $role_name = Role::find($role_id)->name;

        // Get the seeker roles, with the user as a pivot where
        $auth = $seeker->auth->where('user_id', $user_id)->first();
        $this->assertEquals($role_id, $auth->role->id);
        $this->assertEquals($role_name, $auth->role->name);


        // The one above is too long for nothing...
        //$role = $seeker->roleForUser($user_id)
        $this->assertEquals(
            $role_id,
            $this->seekerClass->find($this->seekers[1])->roleForUser($user_id)->id
        );
    }

    /**
     * Basic test, just to make sure.
     * N.B.: Couting on the `auth` table for a given role will return the number
     * of times the role is in uses which can be higher than the number of users
     * (one user could have this role more than once). But counting on the seeker
     * will return the number of users this seeker has because a user, for a
     * given seeker, can only have one role.
     */
    public function test_count()
    {
        // Seeker 1 should have 2 users and seeker 2 should have one
        $seeker_1 = $this->seekerClass->find($this->seekers[1]);
        $seeker_2 = $this->seekerClass->find($this->seekers[2]);

        $this->assertEquals(2, $seeker_1->auth->count());
        $this->assertEquals(1, $seeker_2->auth->count());

        // We try the same with the roles
        // Role 0 has 3 users and role 1 has 1
        // Note here that we only have 2 users defined for the tests
        $role_0 = Role::find($this->roles[0]);
        $role_1 = Role::find($this->roles[1]);

        $this->assertEquals(3, $role_0->auth->count());
        $this->assertEquals(1, $role_1->auth->count());

        // To get unique users, group_by can help. We can't group_by `users` (the relation),
        // but we can groupBy the underlying `user_id`
        $this->assertEquals(2, $role_0->auth->groupBy('user_id')->count());
        $this->assertEquals(1, $role_1->auth->groupBy('user_id')->count());
    }

    /**
     * test_relations function.
     * Mother of all tests
     *
     * @access public
     * @return void
     */
    public function test_relations()
    {
        $this->U_S_R();
        $this->U_R_S();
        $this->R_U_S();
        $this->R_S_U();
        $this->S_R_U();
        $this->S_U_R();
    }

    /**
     * Test the Users => Seekers -> Role relations
     * U    S   R
     * 1    1   1
     * 1    2   1
     * 1    3   3
     * 2    1   3
     * 2    2   1
     * 2    4   2
     */
    public function U_S_R()
    {
        $this->debug("\n ---- U => S -> R ---- ");

        $expected_results = [
            // User 1
            $this->users[0] => [
                $this->seekers[0] => $this->roles[0],
                $this->seekers[1] => $this->roles[0],
                $this->seekers[2] => $this->roles[2]
            ],
            // User 2
            $this->users[1] => [
                $this->seekers[0] => $this->roles[2],
                $this->seekers[1] => $this->roles[0],
                $this->seekers[3] => $this->roles[1]
            ]
        ];

        $results = [];

        $users = User::find($this->users->toArray());
        foreach ($users as $user) {

            $this->debug("\n ---- " . $user->user_name . " ---- ");

            foreach ($user->auth($this->seeker) as $auth)
            {
                $this->debugAuth($auth);
                $results[$auth->user->id][$auth->seeker->id] = $auth->role->id;
            }
        }

        $this->assertEquals($expected_results, $results);
    }

    /**
     * Test the Users => Roles => Seekers relations
     * U    R   S
     * 1    1   1,2
     * 1    3   3
     * 2    1   2
     * 2    2   7
     * 2    3   1
     */
    public function U_R_S()
    {
        $this->debug("\n ---- U => R => S ---- ");

        $expected_results = [
            // User 1
            $this->users[0] => [
                $this->roles[0] => [
                    $this->seekers[0],
                    $this->seekers[1]
                ],
                $this->roles[2] => [
                    $this->seekers[2]
                ]
            ],
            // User 2
            $this->users[1] => [
                $this->roles[2] => [
                    $this->seekers[0]
                ],
                $this->roles[0] => [
                    $this->seekers[1]
                ],
                $this->roles[1] => [
                    $this->seekers[3]
                ]
            ]
        ];

        $results = [];

        $users = User::find($this->users->toArray());
        foreach ($users as $user) {

            $this->debug("\n ---- " . $user->user_name . " ---- ");

            foreach ($user->auth($this->seeker) as $auth)
            {
                $this->debugAuth($auth);
                $results[$auth->user->id][$auth->role->id][] = $auth->seeker->id;
            }
        }

        $this->assertEquals($expected_results, $results);
    }

    /**
     * Test the Roles => Users => Sekers relations
     * R    U   S
     * 1    1   1,2
     * 1    2   2
     * 2    2   4
     * 3    1   3
     * 3    2   1
     */
    public function R_U_S()
    {
        $this->debug("\n ---- R => U => S ---- ");

        $expected_results = [
            // Role 1
            $this->roles[0] => [
                $this->users[0] => [
                    $this->seekers[0],
                    $this->seekers[1]
                ],
                $this->users[1] => [
                    $this->seekers[1]
                ]
            ],
            // Role 2
            $this->roles[1] => [
                $this->users[1] => [
                    $this->seekers[3]
                ]
            ],
            // Role 3
            $this->roles[2] => [
                $this->users[0] => [
                    $this->seekers[2]
                ],
                $this->users[1] => [
                    $this->seekers[0]
                ]
            ]
        ];

        $results = [];

        $roles = Role::find($this->roles->toArray());
        foreach ($roles as $role) {

            $this->debug("\n ---- " . $role->name . " ---- ");

            foreach ($role->auth($this->seeker) as $auth)
            {
                $this->debugAuth($auth);
                $results[$auth->role->id][$auth->user->id][] = $auth->seeker->id;
            }
        }

        $this->assertEquals($expected_results, $results);
    }

    /**
     * Test the Roles => Seekers => Users relations
     * R    S   U
     * 1    1   1
     * 1    2   1,2
     * 2    4   2
     * 3    1   2
     * 3    3   1
     */
    public function R_S_U()
    {
        $this->debug("\n ---- R => S => U ---- ");

        $expected_results = [
            // Role 1
            $this->roles[0] => [
                $this->seekers[0] => [
                    $this->users[0]
                ],
                $this->seekers[1] => [
                    $this->users[0],
                    $this->users[1]
                ]
            ],
            // Role 2
            $this->roles[1] => [
                $this->seekers[3] => [
                    $this->users[1]
                ]
            ],
            // Role 3
            $this->roles[2] => [
                $this->seekers[2] => [
                    $this->users[0]
                ],
                $this->seekers[0] => [
                    $this->users[1]
                ]
            ]
        ];

        $results = [];

        $roles = Role::find($this->roles->toArray());
        foreach ($roles as $role) {

            $this->debug("\n ---- " . $role->name . " ---- ");

            foreach ($role->auth($this->seeker) as $auth)
            {
                $this->debugAuth($auth);
                $results[$auth->role->id][$auth->seeker->id][] = $auth->user->id;
            }
        }

        $this->assertEquals($expected_results, $results);
    }

    /**
     * Test the Seekers => Roles => Users relations
     * S    R   U
     * 1    1   1
     * 1    3   2
     * 2    1   1,2
     * 3    3   1
     * 4    2   2
     */
    public function S_R_U()
    {
        $this->debug("\n ---- S => R => U ---- ");

        $expected_results = [
            // Seeker 1
            $this->seekers[0] => [
                $this->roles[0] => [
                    $this->users[0]
                ],
                $this->roles[2] => [
                    $this->users[1]
                ]
            ],
            // Seeker 2
            $this->seekers[1] => [
                $this->roles[0] => [
                    $this->users[0],
                    $this->users[1]
                ]
            ],
            // Seeker 3
            $this->seekers[2] => [
                $this->roles[2] => [
                    $this->users[0]
                ]
            ],
            // Seeker 4
            $this->seekers[3] => [
                $this->roles[1] => [
                    $this->users[1]
                ]
            ]
        ];

        $results = [];

        $seekers = $this->seekerClass->find($this->seekers->toArray());
        foreach ($seekers as $seeker) {

            $this->debug("\n ---- " . $seeker->id . " ---- ");

            foreach ($seeker->auth as $auth)
            {
                $this->debugAuth($auth);
                $results[$auth->seeker->id][$auth->role->id][] = $auth->user->id;
            }
        }

        $this->assertEquals($expected_results, $results);
    }

    /**
     * Test the Seekers => Users -> Role relations
     * S    U   R
     * 1    1   1
     * 1    2   3
     * 2    1   1
     * 2    2   1
     * 3    1   3
     * 4    2   2
     */
    public function S_U_R()
    {
        $this->debug("\n ---- S => U -> R ---- ");

        $expected_results = [
            // Seeker 1
            $this->seekers[0] => [
                $this->users[0] => $this->roles[0],
                $this->users[1] => $this->roles[2]
            ],
            // Seeker 2
            $this->seekers[1] => [
                $this->users[0] => $this->roles[0],
                $this->users[1] => $this->roles[0]
            ],
            // Seeker 3
            $this->seekers[2] => [
                $this->users[0] => $this->roles[2]
            ],
            // Seeker 4
            $this->seekers[3] => [
                $this->users[1] => $this->roles[1]
            ]
        ];

        $results = [];

        $seekers = $this->seekerClass->find($this->seekers->toArray());
        foreach ($seekers as $seeker) {

            $this->debug("\n ---- " . $seeker->id . " ---- ");

            foreach ($seeker->auth as $auth)
            {
                $this->debugAuth($auth);
                $results[$auth->seeker->id][$auth->user->id] = $auth->role->id;
            }
        }

        $this->assertEquals($expected_results, $results);
    }


    /**
     * debug function.
     *
     * @access protected
     * @param mixed $message
     * @return void
     */
    protected function debug($message)
    {
        if ($this->debug)
        {
            echo "\n" . $message;
        }
    }

    /**
     * debugAuth function.
     *
     * @access protected
     * @param mixed $auth
     * @return void
     */
    protected function debugAuth($auth)
    {
        $seeker_name = isset($auth->seeker->name) ? " - " . $auth->seeker->name : ""; //Name might not exist
        $this->debug("\n USER :: #" . $auth->user->id . " - " . $auth->user->user_name .
                     "\n ROLE :: #" . $auth->role->id . " - " . $auth->role->name .
                     "\n SEEKER :: #" . $auth->seeker->id . $seeker_name . "\n");
    }
}