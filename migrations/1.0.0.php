<?php

    use Illuminate\Database\Schema\Blueprint;

    /**
     * `alt_roles` table that contain all the roles definitions
     */
    if (!$schema->hasTable('alt_roles')) {
        $schema->create('alt_roles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('seeker');
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->engine = 'InnoDB';
            $table->collation = 'utf8_unicode_ci';
            $table->charset = 'utf8';
        });

        // Add default roles
        $roles = [];

        foreach ($roles as $slug => $role) {
            $role->save();
        }
        echo "Created table 'alt_roles'..." . PHP_EOL;
    } else {
        echo "Table 'alt_roles' already exists.  Skipping..." . PHP_EOL;
    }

    /**
     * Many-to-many mapping between permissions and roles.
     */
    if (!$schema->hasTable('alt_permission_roles')) {
        $schema->create('alt_permission_roles', function (Blueprint $table) {
            $table->integer('permission_id')->unsigned();
            $table->integer('role_id')->unsigned();
            $table->timestamps();

            $table->engine = 'InnoDB';
            $table->collation = 'utf8_unicode_ci';
            $table->charset = 'utf8';
            $table->primary(['permission_id', 'role_id']);
            //$table->foreign('permission_id')->references('id')->on('permissions');
            //$table->foreign('role_id')->references('id')->on('roles');
            $table->index('permission_id');
            $table->index('role_id');
        });

        echo "Created table 'alt_permission_roles'..." . PHP_EOL;
    } else {
        echo "Table 'alt_permission_roles' already exists.  Skipping..." . PHP_EOL;
    }

    /**
     * `alt_permissions` that contains the actual permissions slugs
     */
    if (!$schema->hasTable('alt_permissions')) {
        $schema->create('alt_permissions', function(Blueprint $table) {
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

        $defaultRoleIds = [];

        // Add default permissions
        $permissions = [];

        foreach ($permissions as $slug => $permission) {
            $permission->save();
        }

        // Add default mappings to permissions

        echo "Created table 'alt_permissions'..." . PHP_EOL;
    } else {
        echo "Table 'alt_permissions' already exists.  Skipping..." . PHP_EOL;
    }

    /**
     * Many-to-many mapping between roles and users.
     */
    if (!$schema->hasTable('alt_role_users')) {
        $schema->create('alt_role_users', function (Blueprint $table) {
            $table->integer('user_id')->unsigned();
            $table->integer('role_id')->unsigned();
            $table->integer('seeker_id')->unsigned();
            $table->timestamps();

            $table->engine = 'InnoDB';
            $table->collation = 'utf8_unicode_ci';
            $table->charset = 'utf8';
            $table->primary(['user_id', 'role_id']);
            //$table->foreign('user_id')->references('id')->on('users');
            //$table->foreign('role_id')->references('id')->on('roles');
            $table->index('user_id');
            $table->index('role_id');
        });
        echo "Created table 'alt_role_users'..." . PHP_EOL;
    } else {
        echo "Table 'alt_role_users' already exists.  Skipping..." . PHP_EOL;
    }