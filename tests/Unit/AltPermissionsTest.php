<?php

namespace UserFrosting\Tests\Unit;

use UserFrosting\Tests\TestCase;
//use UserFrosting\Tests\DatabaseMigrations;
use UserFrosting\Tests\DatabaseTransactions;
use League\FactoryMuffin\Faker\Facade as Faker;

use UserFrosting\Sprinkle\AltPermissions\Model\User;
use UserFrosting\Sprinkle\AltPermissions\Model\AltRole;
use UserFrosting\Sprinkle\Gaston\Model\Project;

class AltPermissionsTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @var The test data we'll in each test
     */
    protected $testData;

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
        $users = collect($fm->seed(2, 'UserFrosting\Sprinkle\AltPermissions\Model\User'));

        // Create 4 projects
        $projects = collect($fm->seed(4, 'UserFrosting\Sprinkle\Gaston\Model\Project'));

        // Create 3 roles
        $roles =  collect($fm->seed(3, 'UserFrosting\Sprinkle\AltPermissions\Model\AltRole'));

        // Assign them all together
        $users[0]->projects()->sync([
            $projects[0]->id => ['role_id' => $roles[0]->id],
            $projects[1]->id => ['role_id' => $roles[0]->id],
            $projects[2]->id => ['role_id' => $roles[2]->id]
        ]);
        $users[1]->projects()->sync([
            $projects[0]->id => ['role_id' => $roles[2]->id],
            $projects[1]->id => ['role_id' => $roles[0]->id],
            $projects[3]->id => ['role_id' => $roles[1]->id]
        ]);

        // Add everyone to the testData
        $this->testData = (object) [
            'users' => $users->pluck('id'),
            'roles' => $roles->pluck('id'),
            'projects' => $projects->pluck('id')
        ];
    }

    /**
     * Test fetching a user's role for a project
     */
    public function test_individualUser()
    {
        // Get user n° 1
        $user = User::find($this->testData->users[0]);

        // Get project n° 2 id
        $project_id = $this->testData->projects[1];

        // Get user n° 1 role for project n° 2
        $role_id = $user->projects->find($project_id)->pivot->role_id;

        // User n° 1 role for project n° 2 should be n° 1
        $this->assertEquals($this->testData->roles[0], $role_id);

        // Same test, but on one line. We'll improove on that later ;)
        $this->assertEquals(
            $this->testData->roles[0],
            User::find($this->testData->users[0])->projects->find($this->testData->projects[1])->pivot->role_id
        );
    }

    /**
     * Test fetching a project's role for a user
     * Plus make sure we can get a role name too
     */
    public function test_individuaProject()
    {
        // Project 2 object
        $project = Project::find($this->testData->projects[1]);

        // User 1 real id
        $user_id = $this->testData->users[0];

        // Role 1 id and name for result comparaison
        $role_id = $this->testData->roles[0];
        $role_name = AltRole::find($role_id)->name;

        // Get the project roles, with the user as a pivot where
        $user_role = $project->roles()->wherePivot('user_id', $user_id)->first();
        $this->assertEquals($role_id, $user_role->id);
        $this->assertEquals($role_name, $user_role->name);

        // Now get the project user. The role id is avaialable in the pivot.
        // This requires the altRole to be queried again. This is not efficient,
        // but we still test it works. Just to be sure
        $user_role = $project->users($user_id)->first()->pivot->role_id;
        $user_role = AltRole::find($user_role);
        $this->assertEquals($role_id, $user_role->id);
        $this->assertEquals($role_name, $user_role->name);

        // Now we test the custom pivot.
        //$user_role = $project->users($user_id)->first()->role;
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
                "project" => $this->testData->projects[0],
                "role" => $this->testData->roles[0]
            ],
            [
                "user" => $this->testData->users[0],
                "project" => $this->testData->projects[1],
                "role" => $this->testData->roles[0]
            ],
            [
                "user" => $this->testData->users[0],
                "project" => $this->testData->projects[2],
                "role" => $this->testData->roles[2]
            ],
            [
                "user" => $this->testData->users[1],
                "project" => $this->testData->projects[0],
                "role" => $this->testData->roles[2]
            ],
            [
                "user" => $this->testData->users[1],
                "project" => $this->testData->projects[1],
                "role" => $this->testData->roles[0]
            ],
            [
                "user" => $this->testData->users[1],
                "project" => $this->testData->projects[3],
                "role" => $this->testData->roles[1]
            ]
        ]);

        $results = collect([]);

        $users = User::find($this->testData->users->toArray());
        foreach ($users as $user) {

            $projects = $user->projects;
            foreach ($projects as $project) {

                //!TODO -> Custom pivot
                $role_id = $project->pivot->role_id;
                $role = AltRole::find($role_id);

                $collect = [
                    "user" => $user->id,
                    "project" => $project->id,
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
                "project" => $this->testData->projects[0].",".$this->testData->projects[1]
            ],
            [
                "user" => $this->testData->users[0],
                "role" => $this->testData->roles[2],
                "project" => $this->testData->projects[2]
            ],
            [
                "user" => $this->testData->users[1],
                "role" => $this->testData->roles[2],
                "project" => $this->testData->projects[0]
            ],
            [
                "user" => $this->testData->users[1],
                "role" => $this->testData->roles[0],
                "project" => $this->testData->projects[1]
            ],
            [
                "user" => $this->testData->users[1],
                "role" => $this->testData->roles[1],
                "project" => $this->testData->projects[3]
            ]
        ]);

        $results = collect([]);

        $users = User::find($this->testData->users->toArray());
        foreach ($users as $user) {

            $roles = $user->altRole->groupBy('id');
            foreach ($roles as $role_collection) {

                $role = $role_collection->first();

                $projects = collect([]);
                foreach($role_collection as $i) {

                    $project_id = $i->pivot->seeker_id;
                    $project = Project::find($project_id);
                    $projects->push($project);
                }

                $collect = [
                    "user" => $user->id,
                    "role" => $role->id,
                    "project" => $projects->implode("id", ",")
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
                "project" => $this->testData->projects[0].",".$this->testData->projects[1]
            ],
            [
                "role" => $this->testData->roles[0],
                "user" => $this->testData->users[1],
                "project" => $this->testData->projects[1]
            ],
            [
                "role" => $this->testData->roles[1],
                "user" => $this->testData->users[1],
                "project" => $this->testData->projects[3]
            ],
            [
                "role" => $this->testData->roles[2],
                "user" => $this->testData->users[0],
                "project" => $this->testData->projects[2]
            ],
            [
                "role" => $this->testData->roles[2],
                "user" => $this->testData->users[1],
                "project" => $this->testData->projects[0]
            ]
        ]);

        $results = collect([]);

        $roles = AltRole::find($this->testData->roles->toArray());
        foreach ($roles as $role) {

            $users = $role->users->groupBy('id');
            foreach ($users as $user_collection) {

                $user = $user_collection->first();

                $projects = collect([]);
                foreach($user_collection as $i) {

                    $project_id = $i->pivot->seeker_id;
                    $project = Project::find($project_id);
                    $projects->push($project);
                }

                $collect = [
                    "role" => $role->id,
                    "user" => $user->id,
                    "project" => $projects->implode("id", ",")
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
                "project" => $this->testData->projects[0],
                "user" => $this->testData->users[0]
            ],
            [
                "role" => $this->testData->roles[0],
                "project" => $this->testData->projects[1],
                "user" => $this->testData->users[0].",".$this->testData->users[1]
            ],
            [
                "role" => $this->testData->roles[1],
                "project" => $this->testData->projects[3],
                "user" => $this->testData->users[1]
            ],
            [
                "role" => $this->testData->roles[2],
                "project" => $this->testData->projects[2],
                "user" => $this->testData->users[0]
            ],
            [
                "role" => $this->testData->roles[2],
                "project" => $this->testData->projects[0],
                "user" => $this->testData->users[1]
            ]
        ]);

        $results = collect([]);

        $roles = AltRole::find($this->testData->roles->toArray());
        foreach ($roles as $role) {

            $projects = $role->projects->groupBy('id');
            foreach ($projects as $project_collection) {

                $project = $project_collection->first();

                $users = collect([]);
                foreach($project_collection as $i) {

                    $user_id = $i->pivot->user_id;
                    $user = User::find($user_id);
                    $users->push($user);
                }

                $collect = [
                    "role" => $role->id,
                    "project" => $project->id,
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
                "project" => $this->testData->projects[0],
                "role" => $this->testData->roles[0],
                "user" => $this->testData->users[0]
            ],
            [
                "project" => $this->testData->projects[0],
                "role" => $this->testData->roles[2],
                "user" => $this->testData->users[1]
            ],
            [
                "project" => $this->testData->projects[1],
                "role" => $this->testData->roles[0],
                "user" => $this->testData->users[0].",".$this->testData->users[1]
            ],
            [
                "project" => $this->testData->projects[2],
                "role" => $this->testData->roles[2],
                "user" => $this->testData->users[0]
            ],
            [
                "project" => $this->testData->projects[3],
                "role" => $this->testData->roles[1],
                "user" => $this->testData->users[1]
            ]
        ]);

        $results = collect([]);

        $projects = Project::find($this->testData->projects->toArray());
        foreach ($projects as $project) {

            $roles = $project->roles->groupBy('id');
            foreach ($roles as $role_collection) {

                $role = $role_collection->first();

                $users = collect([]);
                foreach($role_collection as $i) {

                    $user_id = $i->pivot->user_id;
                    $user = User::find($user_id);
                    $users->push($user);
                }

                $collect = [
                    "project" => $project->id,
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
                "project" => $this->testData->projects[0],
                "user" => $this->testData->users[0],
                "role" => $this->testData->roles[0]
            ],
            [
                "project" => $this->testData->projects[0],
                "user" => $this->testData->users[1],
                "role" => $this->testData->roles[2]
            ],
            [
                "project" => $this->testData->projects[1],
                "user" => $this->testData->users[0],
                "role" => $this->testData->roles[0]
            ],
            [
                "project" => $this->testData->projects[1],
                "user" => $this->testData->users[1],
                "role" => $this->testData->roles[0]
            ],
            [
                "project" => $this->testData->projects[2],
                "user" => $this->testData->users[0],
                "role" => $this->testData->roles[2]
            ],
            [
                "project" => $this->testData->projects[3],
                "user" => $this->testData->users[1],
                "role" => $this->testData->roles[1]
            ]
        ]);

        $results = collect([]);

        $projects = Project::find($this->testData->projects->toArray());
        foreach ($projects as $project) {

            $users = $project->users;
            foreach ($users as $user) {

                //!TODO -> Custom pivot
                $role_id = $user->pivot->role_id;
                $user->role = AltRole::find($role_id);

                $collect = [
                    "project" => $project->id,
                    "role" => $user->role->id,
                    "user" => $user->id
                ];
                $results->push($collect);
            }
        }

        $this->assertEquals($expected_results, $results);
    }
}