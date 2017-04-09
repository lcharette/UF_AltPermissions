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

    protected $testData;

    protected function setUp()
    {
        // Setup parent first to get access to the container
        parent::setUp();

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

    public function test_userCreation()
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

                //-> Custom pivot
                $role_name = $role->name;

                $collect = [
                    "user" => $user->id,
                    "project" => $project->id,
                    "role" => $role->id
                ];
                $results->push($collect);
                //Debug::debug($user->id . " -> " . $project->id . " -> " . $role->id . " (" . $project->name . " -> " . $role_name . ")");
            }
        }

        /*echo print_r($results, true);
        echo "\n--------------------------\n";
        echo print_r($expected_results, true);*/

        $this->assertEquals($expected_results, $results);
    }

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

                $role_name = $role->name;
                $project_name = $projects->implode("name", ", ");

                $collect = [
                    "user" => $user->id,
                    "role" => $role->id,
                    "project" => $projects->implode("id", ",")
                ];
                $results->push($collect);
                //Debug::debug($user->id . " -> " . $role->id . " -> " . $projects->implode("id", ","). " (" . $role_name . " -> " . $project_name . ")");
            }
        }

        /*echo print_r($results, true);
        echo "\n--------------------------\n";
        echo print_r($expected_results, true);*/

        $this->assertEquals($expected_results, $results);
    }
}