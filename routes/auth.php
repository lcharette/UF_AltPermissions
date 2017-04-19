<?php
 /**
 * UF AltPermissions
 *
 * @link      https://github.com/lcharette/UF-AltPermissions
 * @copyright Copyright (c) 2016 Louis Charette
 * @license   https://github.com/lcharette/UF-AltPermissions/blob/master/licenses/UserFrosting.md (MIT License)
 */

/**
 * Routes for administrative auth management.
 * Route eveything related to the AuthController
 */
$app->group('/api/auth/{seeker}', function () {

    // Auth route. For Auth Sprunje. Load all, for seeker, user or role
    $this->get('/{group}/{id}', 'UserFrosting\Sprinkle\AltPermissions\Controller\AuthController:getList')
         ->setName('api.auth.sprunje');

    $this->post('', 'UserFrosting\Sprinkle\AltPermissions\Controller\AuthController:create')
         ->setName('api.auth.create');

})->add('checkAuthSeeker')->add('authGuard');

$app->group('/api/auth', function () {

    $this->delete('/id/{id}', 'UserFrosting\Sprinkle\AltPermissions\Controller\AuthController:delete')
         ->setName('api.auth.delete');

    $this->put('/id/{id}', 'UserFrosting\Sprinkle\AltPermissions\Controller\AuthController:updateInfo')
         ->setName('api.auth.edit');

})->add('authGuard');

$app->group('/modals/auth', function () {

    $this->get('/create', 'UserFrosting\Sprinkle\AltPermissions\Controller\AuthController:getModalCreate')
         ->setName('modal.auth.create');

    $this->get('/edit/{id}', 'UserFrosting\Sprinkle\AltPermissions\Controller\AuthController:getModalEdit')
         ->setName('modal.auth.edit');

})->add('authGuard');