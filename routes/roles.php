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

    $this->get('/r/{id}', 'UserFrosting\Sprinkle\AltPermissions\Controller\RoleController:pageInfo')
         ->setName('alt_uri_roles.view');

})->add('checkAuthSeeker')->add('authGuard');

$app->group('/api/roles/{seeker}', function () {
    $this->delete('/r/{id}', 'UserFrosting\Sprinkle\AltPermissions\Controller\RoleController:delete')
         ->setName('api.roles.delete');

    $this->get('', 'UserFrosting\Sprinkle\AltPermissions\Controller\RoleController:getList')
         ->setName('api.roles.sprunje');

    $this->get('/auth[/{seeker_id}]', 'UserFrosting\Sprinkle\AltPermissions\Controller\RoleController:getAuthList')
         ->setName('api.roles.auth.sprunje');

    $this->get('/r/{slug}/permissions', 'UserFrosting\Sprinkle\AltPermissions\Controller\RoleController:getPermissions')
         ->setName('api.roles.get.permissions');

    $this->put('/r/{id}/permissions', 'UserFrosting\Sprinkle\AltPermissions\Controller\RoleController:updatePermissions')
         ->setName('api.roles.put.permissions');

    $this->post('', 'UserFrosting\Sprinkle\AltPermissions\Controller\RoleController:create')
         ->setName('api.roles.create.post');

    $this->put('/r/{id}', 'UserFrosting\Sprinkle\AltPermissions\Controller\RoleController:updateInfo')
         ->setName('api.roles.edit.put');

})->add('checkAuthSeeker')->add('authGuard');

$app->group('/modals/roles/{seeker}', function () {
    $this->get('/create', 'UserFrosting\Sprinkle\AltPermissions\Controller\RoleController:getModalCreate')
         ->setName('modal.roles.create');

    $this->get('/edit', 'UserFrosting\Sprinkle\AltPermissions\Controller\RoleController:getModalEdit')
         ->setName('modal.roles.edit');

    $this->get('/permissions', 'UserFrosting\Sprinkle\AltPermissions\Controller\RoleController:getModalEditPermissions')
         ->setName('modal.roles.permissions');

    /*$this->get('/addUsers', 'UserFrosting\Sprinkle\AltPermissions\Controller\RoleController:getModalAddUser')
         ->setName('modal.roles.addUsers');*/

})->add('checkAuthSeeker')->add('authGuard');
