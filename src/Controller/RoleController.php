<?php
 /**
 * UF AltPermissions
 *
 * @link      https://github.com/lcharette/UF-AltPermissions
 * @copyright Copyright (c) 2016 Louis Charette
 * @license   https://github.com/lcharette/UF-AltPermissions/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace UserFrosting\Sprinkle\AltPermissions\Controller;

use Interop\Container\ContainerInterface;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\NotFoundException;
use UserFrosting\Fortress\RequestSchema;
use UserFrosting\Fortress\RequestDataTransformer;
use UserFrosting\Fortress\ServerSideValidator;
use UserFrosting\Fortress\Adapter\JqueryValidationAdapter;
use UserFrosting\Support\Exception\BadRequestException;
use UserFrosting\Support\Exception\ForbiddenException;
use UserFrosting\Sprinkle\Core\Controller\SimpleController;
use UserFrosting\Sprinkle\FormGenerator\Form;

/**
 * Controller class for role-related requests, including listing roles, CRUD for roles, etc.
 *
 * @author Louis Charette (https://github.com/lcharette)
 */
class RoleController extends SimpleController
{
    /**
     *    __construct function.
     *    Create a new ConfigManagerController object.
     *
     *    @param ContainerInterface $ci
     *    @return void
     */
    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;

        // This Sprinkle required FormGenerator Sprinkle. Make sure it's there, otherwise error will be thrown later
        if (!$this->ci->sprinkleManager->isAvailable("FormGenerator")) {
            throw new \Exception("Sprinkle dependencies not met. FormGenerator Sprinkle is not available");
        }
    }

    /**
     *    Renders the modal form for creating a new role.
     *    This does NOT render a complete page.  Instead, it renders the HTML for the modal, which can be embedded in other pages.
     *    This page requires authentication.
     *    Request type: GET
     *
     *    @param  Request $request
     *    @param  Response $response
     *    @param  array $args
     *    @return void
     */
    public function getModalCreate(Request $request, Response $response, $args)
    {
        // GET parameters
        $params = $request->getQueryParams();

        /** @var /UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager */
        $authorizer = $this->ci->authorizer;

        /** @var /UserFrosting\Sprinkle\Account\Database\Models\User $currentUser */
        $currentUser = $this->ci->currentUser;

        /** @var \UserFrosting\Sprinkle\Core\Alert\AlertStream $ms */
        $ms = $this->ci->alerts;

        /** @var \UserFrosting\I18n\MessageTranslator $translator */
        $translator = $this->ci->translator;

        // Request GET data
        $get = $request->getQueryParams();

        // Access-controlled page
        if (!$authorizer->checkAccess($currentUser, 'alt_create_role')) {
            throw new ForbiddenException();
        }

        /** @var \UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = $this->ci->classMapper;

        // Create a dummy role to prepopulate fields
        $role = $classMapper->createInstance('altRole', []);

        // Load validation rules
        $schema = new RequestSchema('schema://altRole/create.json');
        $validator = new JqueryValidationAdapter($schema, $this->ci->translator);

        // Generate the form
        $form = new Form($schema, $role);

        return $this->ci->view->render($response, 'FormGenerator/modal.html.twig', [
            "box_id" => $get['box_id'],
            "box_title" => "ROLE.CREATE",
            "form_action" => $this->ci->router->pathFor('api.roles.create.post', $args),
            "fields" => $form->generate(),
            "validators" => $validator->rules('json', true)
        ]);
    }

    /**
     *    Processes the request to create a new role.
     *
     *    Processes the request from the role creation form, checking that:
     *    1. The role name and slug are not already in use;
     *    2. The user has permission to create a new role;
     *    3. The submitted data is valid.
     *    This route requires authentication (and should generally be limited to admins or the root user).
     *    Request type: POST
     *    @see getModalCreateRole
     *
     *    @param  Request $request
     *    @param  Response $response
     *    @param  array $args
     *    @return void
     */
    public function create(Request $request, Response $response, $args)
    {
        // Get POST parameters: name, slug, description
        $params = $request->getParsedBody();

        /** @var /UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager */
        $authorizer = $this->ci->authorizer;

        /** @var /UserFrosting\Sprinkle\Account\Database\Models\User $currentUser */
        $currentUser = $this->ci->currentUser;

        // Get config service
        $config = $this->ci->config;

        /** @var /UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = $this->ci->classMapper;

        /** @var \UserFrosting\Sprinkle\Core\Alert\AlertStream $ms */
        $ms = $this->ci->alerts;

        /** @var /UserFrosting\Sprinkle\AltPermissions\AccessControlLayer $auth */
        $acl = $this->ci->acl;

        // Access-controlled page
        if (!$authorizer->checkAccess($currentUser, 'alt_create_role')) {
            throw new ForbiddenException();
        }

        // Load the request schema
        $schema = new RequestSchema('schema://altRole/create.json');

        // Whitelist and set parameter defaults
        $transformer = new RequestDataTransformer($schema);
        $data = $transformer->transform($params);

        $error = false;

        // Validate request data
        $validator = new ServerSideValidator($schema, $this->ci->translator);
        if (!$validator->validate($data)) {
            $ms->addValidationErrors($validator);
            $error = true;
        }

        // Check if name or slug already exists
        if ($classMapper->staticMethod('altRole', 'where', 'name', $data['name'])->forSeeker($args['seeker'])->first()) {
            $ms->addMessageTranslated('danger', 'ROLE.NAME_IN_USE', $data);
            $error = true;
        }

        if ($error) {
            return $response->withStatus(400);
        }

        // Insert the seeker class
        $data['seeker'] = $acl->getSeekerModel($args['seeker']);

        // All checks passed!  log events/activities and create role
        // Begin transaction - DB will be rolled back if an exception occurs
        Capsule::transaction( function() use ($classMapper, $data, $ms, $config, $currentUser) {

            // Create the role
            $role = $classMapper->createInstance('altRole', $data);

            // Store new role to database
            $role->save();

            // Create activity record
            $this->ci->userActivityLogger->info("User {$currentUser->user_name} created role {$role->name}.", [
                'type' => 'role_create',
                'user_id' => $currentUser->id
            ]);

            $ms->addMessageTranslated('success', 'ROLE.CREATION_SUCCESSFUL', $data);
        });

        return $response->withJson([], 200, JSON_PRETTY_PRINT);
    }

    /**
     *    Renders the modal form for editing an existing role.
     *
     *    This does NOT render a complete page.  Instead, it renders the HTML for the modal, which can be embedded in other pages.
     *    This page requires authentication.
     *    Request type: GET
     *
     *    @param  Request $request
     *    @param  Response $response
     *    @param  array $args
     *    @return void
     */
    public function getModalEdit(Request $request, Response $response, $args)
    {
        // GET parameters
        $params = $request->getQueryParams();

        /** @var \UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = $this->ci->classMapper;

        /** @var \UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager */
        $authorizer = $this->ci->authorizer;

        /** @var \UserFrosting\Sprinkle\Account\Database\Models\User $currentUser */
        $currentUser = $this->ci->currentUser;

        /** @var \UserFrosting\Sprinkle\Core\Alert\AlertStream $ms */
        $ms = $this->ci->alerts;

        // Get the role
        if (!$role = $classMapper->staticMethod('altRole', 'where', 'id', $params['id'])->first())
        {
            throw new NotFoundException($request, $response);
        }

        // Access-controlled resource - check that currentUser has permission to edit basic fields "name", "slug", "description" for this role
        // !TODO
        /*$fieldNames = ['name', 'slug', 'description'];
        if (!$authorizer->checkAccess($currentUser, 'update_role_field', [
            'role' => $role,
            'fields' => $fieldNames
        ])) {
            throw new ForbiddenException();
        }*/

        // Load validation rules
        $schema = new RequestSchema('schema://altRole/create.json');
        $validator = new JqueryValidationAdapter($schema, $this->ci->translator);

        // Generate the form
        $form = new Form($schema, $role);

        return $this->ci->view->render($response, 'FormGenerator/modal.html.twig', [
            "box_id" => $params['box_id'],
            "box_title" => "ROLE.EDIT",
            "form_action" => $this->ci->router->pathFor('api.roles.edit.put', [
                'seeker' => $args['seeker'],
                'id' => $params['id']
            ]),
            "form_method" => "PUT",
            "fields" => $form->generate(),
            "validators" => $validator->rules('json', true)
        ]);
    }

    /**
     *    Renders the modal form for editing a role's permissions.
     *
     *    This does NOT render a complete page.  Instead, it renders the HTML for the form, which can be embedded in other pages.
     *    This page requires authentication.
     *    Request type: GET
     *
     *    @param  Request $request
     *    @param  Response $response
     *    @param  array $args
     *    @return void
     */
    public function getModalEditPermissions(Request $request, Response $response, $args)
    {
        /** @var /UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager */
        $authorizer = $this->ci->authorizer;

        /** @var /UserFrosting\Sprinkle\Account\Database\Models\User $currentUser */
        $currentUser = $this->ci->currentUser;

        // GET parameters
        $params = $request->getQueryParams();

        // If the role doesn't exist, return 404
        if (!$role = $this->getRoleFromParams($params)) {
            throw new NotFoundException($request, $response);
        }

        // Access-controlled resource - check that currentUser has permission to edit "permissions" field for this role
        if (!$authorizer->checkAccess($currentUser, 'alt_update_role_field', [
            'role' => $role,
            'fields' => ['permissions']
        ])) {
            throw new ForbiddenException();
        }

        return $this->ci->view->render($response, 'modals/role-manage-permissions.html.twig', [
            'role' => $role
        ]);
    }

    /**
     *    Processes the request to update an existing role's details.
     *
     *    Processes the request from the role update form, checking that:
     *    1. The role name/slug are not already in use;
     *    2. The user has the necessary permissions to update the posted field(s);
     *    3. The submitted data is valid.
     *    This route requires authentication (and should generally be limited to admins or the root user).
     *    Request type: PUT
     *    @see getModalRoleEdit
     *
     *    @param  Request $request
     *    @param  Response $response
     *    @param  array $args
     *    @return void
     */
    public function updateInfo(Request $request, Response $response, $args)
    {
        // Get config service
        $config = $this->ci->config;

        // Get PUT parameters: (name, slug, description)
        $params = $request->getParsedBody();

        /** @var /UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager */
        $authorizer = $this->ci->authorizer;

        /** @var /UserFrosting\Sprinkle\Account\Database\Models\User $currentUser */
        $currentUser = $this->ci->currentUser;

        /** @var /UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = $this->ci->classMapper;

        /** @var /UserFrosting\I18n\MessageTranslator $translator */
        $translator = $this->ci->translator;

        /** @var \UserFrosting\Sprinkle\Core\Alert\AlertStream $ms */
        $ms = $this->ci->alerts;

        // Get the role
        if (!$role = $classMapper->staticMethod('altRole', 'where', 'id', $args['id'])->first())
        {
            throw new NotFoundException($request, $response);
        }

        // Load the request schema
        $schema = new RequestSchema('schema://altRole/create.json');

        // Whitelist and set parameter defaults
        $transformer = new RequestDataTransformer($schema);
        $data = $transformer->transform($params);

        $error = false;

        // Validate request data
        $validator = new ServerSideValidator($schema, $translator);
        if (!$validator->validate($data)) {
            $ms->addValidationErrors($validator);
            $error = true;
        }

        // Determine targeted fields
        $fieldNames = [];
        foreach ($data as $name => $value) {
            $fieldNames[] = $name;
        }

        // Access-controlled resource - check that currentUser has permission to edit submitted fields for this role
        // !TODO
        if (!$authorizer->checkAccess($currentUser, 'alt_update_role_field', [
            'role' => $role,
            'fields' => array_values(array_unique($fieldNames))
        ])) {
            throw new ForbiddenException();
        }

        // Check if name or slug already exists
        if (
            isset($data['name']) &&
            $data['name'] != $role->name &&
            $classMapper->staticMethod('altRole', 'where', 'name', $data['name'])->forSeeker($args['seeker'])->first()
        ) {
            $ms->addMessageTranslated('danger', 'ROLE.NAME_IN_USE', $data);
            $error = true;
        }

        if ($error) {
            return $response->withStatus(400);
        }

        // Begin transaction - DB will be rolled back if an exception occurs
        Capsule::transaction( function() use ($data, $role, $currentUser) {
            // Update the role and generate success messages
            foreach ($data as $name => $value) {
                if ($value != $role->$name){
                    $role->$name = $value;
                }
            }

            $role->save();

            // Create activity record
            $this->ci->userActivityLogger->info("User {$currentUser->user_name} updated details for role {$role->name}.", [
                'type' => 'role_update_info',
                'user_id' => $currentUser->id
            ]);
        });

        $ms->addMessageTranslated('success', 'ROLE.UPDATED', [
            'name' => $translator->translate($role->name)
        ]);

        return $response->withJson([], 200, JSON_PRETTY_PRINT);
    }

    /**
     *    Processes the request to delete an existing role.
     *
     *    Deletes the specified role.
     *    Before doing so, checks that:
     *    1. The user has permission to delete this role;
     *    2. The submitted data is valid.
     *    This route requires authentication (and should generally be limited to admins or the root user).
     *    Request type: DELETE
     *
     *    @param  Request $request
     *    @param  Response $response
     *    @param  array $args
     *    @return void
     */
    public function delete(Request $request, Response $response, $args)
    {
        /** @var /UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager */
        $authorizer = $this->ci->authorizer;

        /** @var /UserFrosting\Sprinkle\Account\Database\Models\User $currentUser */
        $currentUser = $this->ci->currentUser;

        /** @var /UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = $this->ci->classMapper;

        /** @var \UserFrosting\Sprinkle\Core\Alert\AlertStream $ms */
        $ms = $this->ci->alerts;

        // Get the role
        if (!$role = $classMapper->staticMethod('altRole', 'where', 'id', $args['id'])->first())
        {
            throw new NotFoundException($request, $response);
        }

        // Access-controlled page
        if (!$authorizer->checkAccess($currentUser, 'alt_delete_role', [
            'role' => $role
        ])) {
            throw new ForbiddenException();
        }

        // Check if there are any users associated with this role
        $countUsers = $role->auth->count();
        if ($countUsers > 0) {
            $e = new BadRequestException();
            $e->addUserMessage('ROLE.HAS_USERS');
            throw $e;
        }

        // Begin transaction - DB will be rolled back if an exception occurs
        Capsule::transaction( function() use ($role, $currentUser) {
            $role->delete();

            // Create activity record
            $this->ci->userActivityLogger->info("User {$currentUser->user_name} deleted role {$role->name}.", [
                'type' => 'role_delete',
                'user_id' => $currentUser->id
            ]);
        });

        $ms->addMessageTranslated('success', 'ROLE.DELETION_SUCCESSFUL', [
            'name' => $role->name
        ]);

        return $response->withJson([], 200, JSON_PRETTY_PRINT);
    }

    /**
     *    Returns a list of Permissions for a specified Role.
     *
     *    Generates a list of permissions, optionally paginated, sorted and/or filtered.
     *    This page requires authentication.
     *    Request type: GET
     *
     *    @param  Request $request
     *    @param  Response $response
     *    @param  array $args
     *    @return void
     */
    public function getPermissions(Request $request, Response $response, $args)
    {
        // GET parameters
        $params = $request->getQueryParams();

        /** @var /UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager */
        $authorizer = $this->ci->authorizer;

        /** @var /UserFrosting\Sprinkle\Account\Database\Models\User $currentUser */
        $currentUser = $this->ci->currentUser;

        /** @var /UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = $this->ci->classMapper;

        // If the role no longer exists, forward to main role listing page
        if (!$role = $this->getRoleFromParams($args)) {
            throw new NotFoundException($request, $response);
        }

        // Access-controlled page
        if (!$authorizer->checkAccess($currentUser, 'alt_view_role_field', [
            'role' => $role,
            'property' => 'permissions'
        ])) {
            throw new ForbiddenException();
        }

        $sprunje = $classMapper->createInstance('permission_sprunje', $classMapper, $params);
        $sprunje->extendQuery(function ($query) use ($role) {
            return $query->forRole($role->id);
        });

        // Be careful how you consume this data - it has not been escaped and contains untrusted user-supplied content.
        // For example, if you plan to insert it into an HTML DOM, you must escape it on the client side (or use client-side templating).
        return $sprunje->toResponse($response);
    }

    /**
     *    Renders a page displaying a role's information, in read-only mode.
     *
     *    This checks that the currently logged-in user has permission to view the requested role's info.
     *    It checks each field individually, showing only those that you have permission to view.
     *    This will also try to show buttons for deleting and editing the role.
     *    This page requires authentication.
     *    Request type: GET
     *
     *    @param  Request $request
     *    @param  Response $response
     *    @param  array $args
     *    @return void
     */
    public function pageInfo(Request $request, Response $response, $args)
    {
        /** @var /UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager */
        $authorizer = $this->ci->authorizer;

        /** @var /UserFrosting\Sprinkle\Account\Database\Models\User $currentUser */
        $currentUser = $this->ci->currentUser;

        /** @var /UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = $this->ci->classMapper;

        // Get the role
        if (!$role = $classMapper->staticMethod('altRole', 'where', 'id', $args['id'])->with('permissions')->first())
        {
            throw new NotFoundException($request, $response);
        }

        // Access-controlled page
        if (!$authorizer->checkAccess($currentUser, 'alt_uri_role', [
                'role' => $role
            ])) {
            throw new ForbiddenException();
        }

        // Get the permissions
        $permissions = $classMapper->staticMethod('altPermission', 'forSeeker', $args['seeker'])->get();

        // Role permissions
        $role_permissions = $role->permissions->pluck('id');

        // Marked as defined the permissions that the role have
        $permissions = $permissions->map(function ($item, $key) use ($role_permissions) {
            $item->active = $role_permissions->contains($item->id);
            return $item;
        });

        return $this->ci->view->render($response, 'pages/altRole.html.twig', [
            'seeker' => $args['seeker'],
            'role' => $role,
            'permissions' => $permissions,
            'uri' => [
                'edit' => $role->getRoute('modal.roles.edit')
            ]
        ]);
    }

    /**
     *    Renders the role listing page.
     *
     *    This page renders a table of roles, with dropdown menus for admin actions for each role.
     *    Actions typically include: edit role, delete role.
     *    This page requires authentication.
     *    Request type: GET
     *
     *    @param  Request $request
     *    @param  Response $response
     *    @param  array $args
     *    @return void
     */
    public function pageList(Request $request, Response $response, $args)
    {
        /** @var /UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager */
        $authorizer = $this->ci->authorizer;

        /** @var /UserFrosting\Sprinkle\Account\Database\Models\User $currentUser */
        $currentUser = $this->ci->currentUser;

        /** @var /UserFrosting\Sprinkle\Core\Router $router */
        $router = $this->ci->router;

        // Access-controlled page
        if (!$authorizer->checkAccess($currentUser, 'alt_uri_role')) {
            throw new ForbiddenException();
        }

        return $this->ci->view->render($response, 'pages/altRoles.html.twig', [
            'seeker' => $args['seeker'],
            'uri' => [
                'create' => $router->pathFor('modal.roles.create', $args),
                'sprunje' => $router->pathFor('api.roles.sprunje', $args)
            ]
        ]);
    }

    /**
     *    Returns a list of Roles
     *
     *    Generates a list of roles, optionally paginated, sorted and/or filtered.
     *    This page requires authentication.
     *    Request type: GET
     *
     *    @param  Request $request
     *    @param  Response $response
     *    @param  array $args
     *    @return void
     */
    public function getList(Request $request, Response $response, $args)
    {
        // GET parameters
        $params = $request->getQueryParams();

        /** @var /UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager */
        $authorizer = $this->ci->authorizer;

        /** @var /UserFrosting\Sprinkle\Account\Database\Models\User $currentUser */
        $currentUser = $this->ci->currentUser;

        /** @var /UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = $this->ci->classMapper;

        // Access-controlled page
        if (!$authorizer->checkAccess($currentUser, 'alt_uri_role')) {
            throw new ForbiddenException();
        }

        $sprunje = $classMapper->createInstance('altRole_sprunje', $classMapper, $params, $args['seeker']);
        //Debug::debug(print_r($sprunje, true));

        // Be careful how you consume this data - it has not been escaped and contains untrusted user-supplied content.
        // For example, if you plan to insert it into an HTML DOM, you must escape it on the client side (or use client-side templating).
        return $sprunje->toResponse($response);
    }

    /**
     *    Processes the request to update a specific field for an existing role, including permissions.
     *
     *    Processes the request from the role update form, checking that:
     *    1. The logged-in user has the necessary permissions to update the putted field(s);
     *    2. The submitted data is valid.
     *    This route requires authentication.
     *    Request type: PUT
     *
     *    @param  Request $request
     *    @param  Response $response
     *    @param  array $args
     *    @return void
     */
    public function updatePermissions(Request $request, Response $response, $args)
    {
        // Get PUT parameters: value
        $put = $request->getParsedBody();

        /** @var /UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager */
        $authorizer = $this->ci->authorizer;

        /** @var /UserFrosting\Sprinkle\Account\Database\Models\User $currentUser */
        $currentUser = $this->ci->currentUser;

        // Get config service
        $config = $this->ci->config;

        /** @var \UserFrosting\Sprinkle\Core\Alert\AlertStream $ms */
        $ms = $this->ci->alerts;

        /** @var /UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = $this->ci->classMapper;

        // Get the role
        if (!$role = $classMapper->staticMethod('altRole', 'where', 'id', $args['id'])->first())
        {
            throw new NotFoundException($request, $response);
        }

        // Access-controlled resource - check that currentUser has permission to edit the specified field for this user
        //!TODO
        /*if (!$authorizer->checkAccess($currentUser, 'update_role_field', [
            'role' => $role,
            'fields' => [$fieldName]
        ])) {
            throw new ForbiddenException();
        }*/

        if (!isset($put['permissions'])) {
            throw new BadRequestException();
        }

        // The checkbox are defined as a $permission_id => $active key/pair array. We need to find only the keys that are active
        $newPermissions = collect($put['permissions'])->filter(function ($value, $key) {
            return $value == 1;
        })->keys();

        // Begin transaction - DB will be rolled back if an exception occurs
        Capsule::transaction( function() use ($newPermissions, $role, $currentUser) {

            $role->permissions()->sync($newPermissions);

            // Create activity record
            $this->ci->userActivityLogger->info("User {$currentUser->user_name} updated permissions for role {$role->name}.", [
                'type' => 'role_update_field',
                'user_id' => $currentUser->id
            ]);
        });

        // Add success messages
        $ms->addMessageTranslated('success', 'ROLE.PERMISSIONS_UPDATED', [
            'name' => $role->name
        ]);

        return $response->withJson([], 200, JSON_PRETTY_PRINT);
    }
}
