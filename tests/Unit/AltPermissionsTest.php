<?php

namespace UserFrosting\Tests\Unit;

use UserFrosting\Tests\TestCase;
//use Illuminate\Foundation\Testing\DatabaseMigrations;
//use Illuminate\Foundation\Testing\DatabaseTransactions;

use UserFrosting\Sprinkle\Account\Model\User;
use UserFrosting\Sprinkle\AltPermissions\Model\AltRole;
use UserFrosting\Sprinkle\Gaston\Model\Project;

class AltPermissionsTest extends TestCase
{
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

        $diff = $results->diff([2, 4, 6, 8]);
        $this->assertEmpty($diff->all(), "U => S -> R Failed");
    }
}