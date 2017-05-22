<?php
 /**
 * UF AltPermissions
 *
 * @link      https://github.com/lcharette/UF-AltPermissions
 * @copyright Copyright (c) 2016 Louis Charette
 * @license   https://github.com/lcharette/UF-AltPermissions/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace UserFrosting\Sprinkle\AltPermissions\Model\Migrations\v100;

use UserFrosting\System\Bakery\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;

/**
 * altPermissionsRole table migration
 * @extends Migration
 */
class AltPermissionsRolesTable extends Migration
{
    /**
     * {@inheritDoc}
     */
    public $dependencies = [
        '\UserFrosting\Sprinkle\AltPermissions\Model\Migrations\v100\AltPermissionsTable',
        '\UserFrosting\Sprinkle\AltPermissions\Model\Migrations\v100\AltRolesTable'
    ];

    /**
     * {@inheritDoc}
     */
    public function up()
    {
        if (!$this->schema->hasTable('alt_permission_roles')) {
            $this->schema->create('alt_permission_roles', function (Blueprint $table) {
                $table->integer('permission_id')->unsigned();
                $table->integer('role_id')->unsigned();
                $table->timestamps();

                $table->engine = 'InnoDB';
                $table->collation = 'utf8_unicode_ci';
                $table->charset = 'utf8';
                $table->primary(['permission_id', 'role_id']);
                $table->foreign('permission_id')->references('id')->on('alt_permissions');
                $table->foreign('role_id')->references('id')->on('alt_roles');
                $table->index('permission_id');
                $table->index('role_id');
            });
        }
    }

    /**
     * {@inheritDoc}
     */
    public function down()
    {
        $this->schema->drop('alt_permission_roles');
    }
}