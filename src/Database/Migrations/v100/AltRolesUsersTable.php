<?php
 /**
 * UF AltPermissions
 *
 * @link      https://github.com/lcharette/UF-AltPermissions
 * @copyright Copyright (c) 2016 Louis Charette
 * @license   https://github.com/lcharette/UF-AltPermissions/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace UserFrosting\Sprinkle\AltPermissions\Database\Migrations\v100;

use UserFrosting\Sprinkle\Core\Database\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * altRoleUsers table migration
 */
class AltRolesUsersTable extends Migration
{
    /**
     * {@inheritDoc}
     */
    static public $dependencies = [
        '\UserFrosting\Sprinkle\Account\Database\Migrations\v400\UsersTable',
        '\UserFrosting\Sprinkle\AltPermissions\Database\Migrations\v100\AltPermissionsRolesTable'
    ];

    /**
     * {@inheritDoc}
     */
    public function up()
    {
        if (!$this->schema->hasTable('alt_role_users')) {
            $this->schema->create('alt_role_users', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('role_id');
                $table->unsignedInteger('seeker_id');
                $table->string('seeker_type');
                $table->nullableTimestamps();

                $table->engine = 'InnoDB';
                $table->collation = 'utf8_general_ci';
                $table->charset = 'utf8';
                $table->index(['user_id', 'role_id']);
                $table->foreign('user_id')->references('id')->on('users');
                $table->foreign('role_id')->references('id')->on('alt_roles');
            });
        }
    }

    /**
     * {@inheritDoc}
     */
    public function down()
    {
        $this->schema->drop('alt_role_users');
    }
}
