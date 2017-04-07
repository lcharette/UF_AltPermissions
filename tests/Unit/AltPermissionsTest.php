<?php

namespace UserFrosting\Tests\Unit;

use UserFrosting\Tests\TestCase;
//use UserFrosting\Tests\DatabaseMigrations;
use UserFrosting\Tests\DatabaseTransactions;

use UserFrosting\Sprinkle\AltPermissions\Model\User;
use UserFrosting\Sprinkle\AltPermissions\Model\AltRole;
use UserFrosting\Sprinkle\Gaston\Model\Project;

class AltPermissionsTest extends TestCase
{
    use DatabaseTransactions;

    protected $testData;

    protected function setUp()
    {
        $this->testData = collect([]);


        // Create 2 users
        /*$user_1 = new User([

        ])->save();*/
        //$user_2

        // Create 4 projects
        /*$project_1
        $project_2
        $project_3
        $project_4

        // Create 3 roles
        $role_1
        $role_2
        $role_3

        // Assign them all together
        */

        parent::setUp();
    }

    public function test__U_S_R()
    {
        $results = collect([]);

        $users = User::get();
        foreach ($users as $user) {

            $projects = $user->projects;
            foreach ($projects as $project) {

                //!TODO -> Custom pivot
                $role_id = $project->pivot->role_id;
                $role = AltRole::find($role_id);

                //-> Custom pivot
                $role_name = $role->name;

                $collect = collect([
                    $user->id,
                    $project->id,
                    $role->id
                ]);
                $results->push($collect);
                //Debug::debug($user->id . " -> " . $project->id . " -> " . $role->id . " (" . $project->name . " -> " . $role_name . ")");
            }
        }

        //echo print_r($results, true);

        $diff = $results->diff([2, 4, 6, 8]);
        $this->assertEmpty($diff->all(), "U => S -> R Failed");
    }
}