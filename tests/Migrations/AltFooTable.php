<?php
 /**
 * UF AltPermissions
 *
 * @link      https://github.com/lcharette/UF-AltPermissions
 * @copyright Copyright (c) 2016 Louis Charette
 * @license   https://github.com/lcharette/UF-AltPermissions/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace UserFrosting\Sprinkle\AltPermissions\Tests\Migrations;

use UserFrosting\System\Bakery\Migration;
use Illuminate\Database\Schema\Blueprint;

/**
 * altFoo table migration
 */
class AltFooTable extends Migration
{
    /**
     * {@inheritDoc}
     */
    static public $dependencies = [
        '\UserFrosting\Sprinkle\AltPermissions\Database\Migrations\v100\AltPermissionsRolesTable'
    ];

    /**
     * {@inheritDoc}
     */
    public function up()
    {
        if (!$this->schema->hasTable('alt_foo')) {
            $this->schema->create('alt_foo', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->engine = 'InnoDB';
                $table->collation = 'utf8_unicode_ci';
                $table->charset = 'utf8';
            });
        }
    }

    /**
     * {@inheritDoc}
     */
    public function down()
    {
        $this->schema->drop('alt_foo');
    }
}