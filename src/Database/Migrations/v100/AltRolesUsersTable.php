<?php
 /**
 * UF AltPermissions
 *
 * @link      https://github.com/lcharette/UF-AltPermissions
 * @copyright Copyright (c) 2016 Louis Charette
 * @license   https://github.com/lcharette/UF-AltPermissions/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace UserFrosting\Sprinkle\AltPermissions\Database\Migrations\v100;

use UserFrosting\System\Bakery\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

/**
 * altRoleUsers table migration
 * @extends Migration
 */
class AltRolesUsersTable extends Migration
{
    /**
     * {@inheritDoc}
     */
    public $dependencies = [
        '\UserFrosting\Sprinkle\Account\Database\Migrations\v400\UsersTable',
        '\UserFrosting\Sprinkle\AltPermissions\Database\Migrations\v100\AltRolesTable'
    ];

    /**
     * {@inheritDoc}
     */
    public function up()
    {
        if (!$this->schema->hasTable('alt_role_users')) {
            $this->schema->create('alt_role_users', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id')->unsigned();
                $table->integer('role_id')->unsigned();
                $table->integer('seeker_id')->unsigned();
                $table->string('seeker_type');
                $table->timestamps();

                $table->engine = 'InnoDB';
                $table->collation = 'utf8_unicode_ci';
                $table->charset = 'utf8';
                $table->primary(['user_id', 'role_id']);
                $table->foreign('user_id')->references('id')->on('users');
                $table->foreign('role_id')->references('id')->on('alt_roles');
                $table->index('user_id');
                $table->index('role_id');
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