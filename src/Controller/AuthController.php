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
use UserFrosting\Sprinkle\FormGenerator\RequestSchema;
use UserFrosting\Fortress\RequestDataTransformer;
use UserFrosting\Fortress\ServerSideValidator;
use UserFrosting\Fortress\Adapter\JqueryValidationAdapter;

use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\NotFoundException;
use UserFrosting\Sprinkle\Account\Model\User;
use UserFrosting\Sprinkle\Core\Controller\SimpleController;
use UserFrosting\Sprinkle\Core\Facades\Debug;
use UserFrosting\Support\Exception\BadRequestException;
use UserFrosting\Support\Exception\ForbiddenException;
use UserFrosting\Support\Exception\HttpException;

/**
 * Controller class for role-related requests, including listing roles, CRUD for roles, etc.
 *
 * @author Louis Charette (https://github.com/lcharette)
 */
class AuthController extends SimpleController
{
    /**
     * __construct function.
     * Create a new ConfigManagerController object.
     *
     * @access public
     * @param ContainerInterface $ci
     * @return void
     * OK
     */
    public function __construct(ContainerInterface $ci) {
        $this->ci = $ci;

        // This Sprinkle required FormGenerator Sprinkle. Make sure it's there, otherwise error will be thrown later
        if (!$this->ci->sprinkleManager->isAvailable("FormGenerator")) {
            throw new \Exception("Sprinkle dependencies not met. FormGenerator Sprinkle is not available");
        }
    }

    /**
     * Renders the modal form for creating a new role.
     *
     * This does NOT render a complete page.  Instead, it renders the HTML for the modal, which can be embedded in other pages.
     * This page requires authentication.
     * Request type: GET
     * OK
     */
    public function getModalCreate($request, $response, $args)
    {
        // GET parameters
        $params = $request->getQueryParams();

        /** @var UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager */
        $authorizer = $this->ci->authorizer;

        /** @var UserFrosting\Sprinkle\Account\Model\User $currentUser */
        $currentUser = $this->ci->currentUser;

        /** @var MessageStream $ms */
        $ms = $this->ci->alerts;

        $translator = $this->ci->translator;

        // Request GET data
        $get = $request->getQueryParams();

        // Access-controlled page
        if (!$authorizer->checkAccess($currentUser, 'create_role')) {
            throw new ForbiddenException();
        }

        /** @var UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = $this->ci->classMapper;

        // Create a dummy role to prepopulate fields
        $role = $classMapper->createInstance('altRole', []);

        // Load validation rules
        $schema = new RequestSchema('schema://altRole/create.json');
        $validator = new JqueryValidationAdapter($schema, $this->ci->translator);

        // Generate the form
        $schema->initForm($role);

        // Add info box about the language keys
        $ms->addMessageTranslated('info', 'ALT_ROLE.INFO_LANGUAGE');

        return $this->ci->view->render($response, 'FormGenerator/modal.html.twig', [
            "box_id" => $get['box_id'],
            "box_title" => "ROLE.CREATE",
            "form_action" => $this->ci->get('router')->pathFor('api.roles.create.post', $args),
            "fields" => $schema->generateForm(),
            "validators" => $validator->rules()
        ]);
    }

    /**
     * Processes the request to create a new role.
     *
     * Processes the request from the role creation form, checking that:
     * 1. The role name and slug are not already in use;
     * 2. The user has permission to create a new role;
     * 3. The submitted data is valid.
     * This route requires authentication (and should generally be limited to admins or the root user).
     * Request type: POST
     * @see getModalCreateRole
     * OK
     */
    public function create($request, $response, $args)
    {
        // Get POST parameters: name, slug, description
        $params = $request->getParsedBody();

        /** @var UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager */
        $authorizer = $this->ci->authorizer;

        /** @var UserFrosting\Sprinkle\Account\Model\User $currentUser */
        $currentUser = $this->ci->currentUser;

        /** @var Config $config */
        $config = $this->ci->config;

        /** @var UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = $this->ci->classMapper;

        // Access-controlled page
        if (!$authorizer->checkAccess($currentUser, 'create_role')) {
            throw new ForbiddenException();
        }

        /** @var MessageStream $ms */
        $ms = $this->ci->alerts;

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

        // Insert the seeker
        $data['seeker'] = $args['seeker'];

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

        return $response->withStatus(200);
    }

    /**
     * Renders the modal form for editing an existing role.
     *
     * This does NOT render a complete page.  Instead, it renders the HTML for the modal, which can be embedded in other pages.
     * This page requires authentication.
     * Request type: GET
     */
    public function getModalEdit($request, $response, $args)
    {
        // GET parameters
        $params = $request->getQueryParams();

        /** @var UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = $this->ci->classMapper;

        /** @var UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager */
        $authorizer = $this->ci->authorizer;

        /** @var UserFrosting\Sprinkle\Account\Model\User $currentUser */
        $currentUser = $this->ci->currentUser;

        /** @var MessageStream $ms */
        $ms = $this->ci->alerts;

        // Get the role
        if (!$auth = $classMapper->staticMethod('altAuth', 'find', $args['id']))
        {
            throw new NotFoundException($request, $response);
        }

        // Access-controlled resource - check that currentUser has permission to edit basic fields "name", "slug", "description" for this role
        // !TODO
        /*$fieldNames = ['name', 'slug', 'description'];
        if (!$authorizer->checkAccess($currentUser, 'update_role_field', [
            'role' => $auth,
            'fields' => $fieldNames
        ])) {
            throw new ForbiddenException();
        }*/

        // Load validation rules
        $schema = new RequestSchema('schema://altRole/auth-edit.json');
        $validator = new JqueryValidationAdapter($schema, $this->ci->translator);

        // Generate the form. The value will be set manually later
        $schema->initForm();

        // Fill in the roles
        $possibleRoles = $classMapper->staticMethod('altRole', 'where', 'seeker', $auth->seeker_type)->get();

        // Create the select association. Don't need to translate, FormGenerator does it automatically
        $roleSelect = $possibleRoles->pluck('name', 'id');

        // We pass the compagnie as the option of the selects
        $schema->setInputArgument("role", "options", $roleSelect);

        // We need to the the value manually. If we pass the relation, it will associate the relation
        // as the value of the select
        $schema->setValue("role", $auth->role_id);

        return $this->ci->view->render($response, 'FormGenerator/modal.html.twig', [
            "box_id" => $params['box_id'],
            "box_title" => "ROLE.EDIT",
            "form_action" => $this->ci->get('router')->pathFor('api.auth.edit', [
                'id' => $args['id']
            ]),
            "form_method" => "PUT",
            "fields" => $schema->generateForm(),
            "validators" => $validator->rules()
        ]);
    }

    /**
     * Processes the request to update an existing role's details.
     *
     * Processes the request from the role update form, checking that:
     * 1. The role name/slug are not already in use;
     * 2. The user has the necessary permissions to update the posted field(s);
     * 3. The submitted data is valid.
     * This route requires authentication (and should generally be limited to admins or the root user).
     * Request type: PUT
     * @see getModalRoleEdit
     */
    public function updateInfo($request, $response, $args)
    {
        /** @var Config $config */
        $config = $this->ci->config;

        // Get PUT parameters: (name, slug, description)
        $params = $request->getParsedBody();

        /** @var UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager */
        $authorizer = $this->ci->authorizer;

        /** @var UserFrosting\Sprinkle\Account\Model\User $currentUser */
        $currentUser = $this->ci->currentUser;

        /** @var UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = $this->ci->classMapper;

        $translator = $this->ci->translator;

        /** @var MessageStream $ms */
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
        if (!$authorizer->checkAccess($currentUser, 'update_role_field', [
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

        return $response->withStatus(200);
    }

    /**
     * Processes the request to delete an existing role.
     *
     * Deletes the specified role.
     * Before doing so, checks that:
     * 1. The user has permission to delete this role;
     * 2. The role is not a default for new users;
     * 3. The role does not have any associated users;
     * 4. The submitted data is valid.
     * This route requires authentication (and should generally be limited to admins or the root user).
     * Request type: DELETE
     * OK
     */
    public function delete($request, $response, $args)
    {
        /** @var UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager */
        $authorizer = $this->ci->authorizer;

        /** @var UserFrosting\Sprinkle\Account\Model\User $currentUser */
        $currentUser = $this->ci->currentUser;

        /** @var UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = $this->ci->classMapper;

        /** @var MessageStream $ms */
        $ms = $this->ci->alerts;

        // Get the role
        if (!$role = $classMapper->staticMethod('altRole', 'where', 'id', $args['id'])->first())
        {
            throw new NotFoundException($request, $response);
        }

        // Access-controlled page
        if (!$authorizer->checkAccess($currentUser, 'delete_role', [
            'role' => $role
        ])) {
            throw new ForbiddenException();
        }

        // Check that we are not deleting a default role
        //$defaultRoleSlugs = $classMapper->staticMethod('altRole', 'getDefaultSlugs');

        // Need to use loose comparison for now, because some DBs return `id` as a string
        /*if (in_array($role->slug, $defaultRoleSlugs)) {
            $e = new BadRequestException();
            $e->addUserMessage('ROLE.DELETE_DEFAULT');
            throw $e;
        }*/

        // Check if there are any users associated with this role
        $countUsers = $role->auth->count();
        if ($countUsers > 0) {
            $e = new BadRequestException();
            $e->addUserMessage('ROLE.HAS_USERS');
            throw $e;
        }

        $roleName = $role->getLocaleName();

        // Begin transaction - DB will be rolled back if an exception occurs
        Capsule::transaction( function() use ($role, $roleName, $currentUser) {
            $role->delete();
            unset($role);

            // Create activity record
            $this->ci->userActivityLogger->info("User {$currentUser->user_name} deleted role {$roleName}.", [
                'type' => 'role_delete',
                'user_id' => $currentUser->id
            ]);
        });

        $ms->addMessageTranslated('success', 'ROLE.DELETION_SUCCESSFUL', [
            'name' => $roleName
        ]);

        return $response->withStatus(200);
    }

    /**
     * Returns a list of auth data
     *
     * Generates a list of auth data, optionally paginated, sorted and/or filtered.
     * This page requires authentication.
     * Request type: GET
     */
    public function getList($request, $response, $args)
    {
        // GET parameters
        $params = $request->getQueryParams();

        /** @var UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager */
        $authorizer = $this->ci->authorizer;

        /** @var UserFrosting\Sprinkle\Account\Model\User $currentUser */
        $currentUser = $this->ci->currentUser;

        // Access-controlled page
        if (!$authorizer->checkAccess($currentUser, 'uri_roles')) {
            throw new ForbiddenException();
        }

        /** @var UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = $this->ci->classMapper;

        // Make sure the group arguments are valid
        if (in_array($args['group'], ["seeker", "user", "role"])) {
            $where = [$args['group']."_id" => $args['id']];
        } else {
            $where = [];
        }

        // Get the sprunje
        $sprunje = $classMapper->createInstance('auth_sprunje', $classMapper, $params, $args['seeker'], $where);

        // Be careful how you consume this data - it has not been escaped and contains untrusted user-supplied content.
        // For example, if you plan to insert it into an HTML DOM, you must escape it on the client side (or use client-side templating).
        return $sprunje->toResponse($response);
    }
}
