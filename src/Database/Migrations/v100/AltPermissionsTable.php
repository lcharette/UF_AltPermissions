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

/**
 * altPermissions table migration
 */
class AltPermissionsTable extends Migration
{
    /**
     * {@inheritDoc}
     */
    public function up()
    {
        if (!$this->schema->hasTable('alt_permissions')) {
            $this->schema->create('alt_permissions', function (Blueprint $table) {
                $table->increments('id');
                $table->string('slug');
                $table->string('seeker');
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
        $this->schema->drop('alt_permissions');
    }
}