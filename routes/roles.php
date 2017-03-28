<?php
 /**
 * UF AltPermissions
 *
 * @link      https://github.com/lcharette/UF-AltPermissions
 * @copyright Copyright (c) 2016 Louis Charette
 * @license   https://github.com/lcharette/UF-AltPermissions/blob/master/licenses/UserFrosting.md (MIT License)
 */

/**
 * Routes for administrative role management.
 */
$app->group('/admin/roles/{seeker}', function () {
    $this->get('', 'UserFrosting\Sprinkle\AltPermissions\Controller\RoleController:pageList')
        ->setName('alt_uri_roles');

    $this->get('/r/{slug}', 'UserFrosting\Sprinkle\AltPermissions\Controller\RoleController:pageInfo');
})->add('checkAuthSeeker')->add('authGuard');

$app->group('/api/roles/{seeker}', function () {
    $this->delete('/r/{id}', 'UserFrosting\Sprinkle\AltPermissions\Controller\RoleController:delete')
         ->setName('api.roles.delete');

    $this->get('', 'UserFrosting\Sprinkle\AltPermissions\Controller\RoleController:getList');

    $this->get('/r/{slug}', 'UserFrosting\Sprinkle\AltPermissions\Controller\RoleController:getInfo');

    $this->get('/r/{slug}/permissions', 'UserFrosting\Sprinkle\AltPermissions\Controller\RoleController:getPermissions');

    $this->post('', 'UserFrosting\Sprinkle\AltPermissions\Controller\RoleController:create')
         ->setName('api.roles.create.post');

    $this->put('/r/{id}', 'UserFrosting\Sprinkle\AltPermissions\Controller\RoleController:updateInfo')
         ->setName('api.roles.edit.post');

    $this->put('/r/{slug}/{field}', 'UserFrosting\Sprinkle\AltPermissions\Controller\RoleController:updateField');
})->add('checkAuthSeeker')->add('authGuard');

$app->group('/modals/roles/{seeker}', function () {
    $this->get('/create', 'UserFrosting\Sprinkle\AltPermissions\Controller\RoleController:getModalCreate');

    $this->get('/edit', 'UserFrosting\Sprinkle\AltPermissions\Controller\RoleController:getModalEdit')
         ->setName('api.roles.edit.form');

    $this->get('/permissions', 'UserFrosting\Sprinkle\AltPermissions\Controller\RoleController:getModalEditPermissions');
})->add('checkAuthSeeker')->add('authGuard');
