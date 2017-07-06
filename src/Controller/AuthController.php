<?php
 /**
 * UF AltPermissions
 *
 * @link      https://github.com/lcharette/UF-AltPermissions
 * @copyright Copyright (c) 2016 Louis Charette
 * @license   https://github.com/lcharette/UF-AltPermissions/blob/master/licenses/UserFrosting.md (MIT License)
 */
namespace UserFrosting\Sprinkle\AltPermissions\Controller;

use Illuminate\Database\Capsule\Manager as Capsule;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\NotFoundException;
use Interop\Container\ContainerInterface;
use UserFrosting\Fortress\RequestSchema;
use UserFrosting\Fortress\RequestDataTransformer;
use UserFrosting\Fortress\ServerSideValidator;
use UserFrosting\Fortress\Adapter\JqueryValidationAdapter;
use UserFrosting\Support\Exception\BadRequestException;
use UserFrosting\Support\Exception\ForbiddenException;
use UserFrosting\Support\Exception\HttpException;
use UserFrosting\Sprinkle\AltPermissions\Database\Models\User;
use UserFrosting\Sprinkle\Core\Controller\SimpleController;
use UserFrosting\Sprinkle\Core\Facades\Debug;
use UserFrosting\Sprinkle\FormGenerator\Form;

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
        /** @var UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager */
        $authorizer = $this->ci->authorizer;

        /** @var UserFrosting\Sprinkle\Account\Database\Models\User $currentUser */
        $currentUser = $this->ci->currentUser;

        /** @var MessageStream $ms */
        $ms = $this->ci->alerts;

        /** @var UserFrosting\I18n\MessageTranslator $translator */
        $translator = $this->ci->translator;

        /** @var UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = $this->ci->classMapper;

        /** @var UserFrosting\Sprinkle\Core\Router $router */
        $router = $this->ci->router;

        // Request GET data
        $get = $request->getQueryParams();

        // Access-controlled page
        /*
        //!TODO
        if (!$authorizer->checkAccess($currentUser, 'create_role')) {
            throw new ForbiddenException();
        }*/

        // Load validation rules
        $schema = new RequestSchema('schema://altRole/auth-create.json');
        $validator = new JqueryValidationAdapter($schema, $this->ci->translator);

        // Generate the form
        $form = new Form($schema);

        // Fill in the possibles roles.
        $possibleRoles = $classMapper->staticMethod('altRole', 'forSeeker', $args['seeker'])->get();

        // Create the select association. Don't need to translate, FormGenerator does it automatically
        $roleSelect = $possibleRoles->pluck('name', 'id');

        // We pass the roles as the option of the selects
        $form->setInputArgument("role", "options", $roleSelect);

        // Get the modal title. Depend if the seeker specific key is define
        $seekerTitle = "AUTH.".strtoupper($args['seeker']).".ADD_USER";
        $boxTitle = $translator->has($seekerTitle) ? $seekerTitle : "AUTH.ADD_USER";

        // Using custom form here to add the javascript we need fo Typeahead.
        $this->ci->view->render($response, "FormGenerator/userSelect.html.twig", [
            "box_id" => $get['box_id'],
            "box_title" => $boxTitle,
            "form_action" => $router->pathFor('api.auth.create', $args),
            "fields" => $form->generate(),
            "collection_placeholder" => 'USER.SELECT',
            "collection_api" => $router->pathFor('api.autocomplete.auth.username', $args),
            "validators" => $validator->rules('json', true)
        ]);
    }

    /**
     * getUserList function.
     * Function for the custom sprunje returning the list of user NOT in the Seeker
     *
     * @access public
     * @param mixed $request
     * @param mixed $response
     * @param mixed $args
     * @return void
     */
    public function getUserList($request, $response, $args) {

        // GET parameters
        $params = $request->getQueryParams();

        /** @var UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager */
        $authorizer = $this->ci->authorizer;

        /** @var UserFrosting\Sprinkle\Account\Database\Models\User $currentUser */
        $currentUser = $this->ci->currentUser;

        /** @var UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = $this->ci->classMapper;

        // Access-controlled page
        // !TODO
        /*if (!$authorizer->checkAccess($currentUser, 'uri_roles')) {
            throw new ForbiddenException();
        }*/

        // Get the sprunje
        $sprunje = $classMapper->createInstance('authUser_sprunje', $classMapper, $params, $args['seeker'], $args['id']);

        // Be careful how you consume this data - it has not been escaped and contains untrusted user-supplied content.
        // For example, if you plan to insert it into an HTML DOM, you must escape it on the client side (or use client-side templating).
        return $sprunje->toResponse($response);
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

        /** @var UserFrosting\Sprinkle\Account\Database\Models\User $currentUser */
        $currentUser = $this->ci->currentUser;

        /** @var UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = $this->ci->classMapper;

        /** @var UserFrosting\I18n\MessageTranslator $translator */
        $translator = $this->ci->translator;

        /** @var MessageStream $ms */
        $ms = $this->ci->alerts;

        // Access-controlled page
        //!TODO
        /*if (!$authorizer->checkAccess($currentUser, 'create_role')) {
            throw new ForbiddenException();
        }*/

        // Load the request schema
        $schema = new RequestSchema('schema://altRole/auth-create.json');

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

        // User must exist
        if (!$user = $classMapper->staticMethod('altUser', 'find', $data['user'])) {
            $ms->addMessageTranslated('danger', 'USER_ID_INVALID');
            $error = true;
        }

        // Check the selected role exist
        if (!$role = $classMapper->staticMethod('altRole', 'find', $data['role'])) {
            $ms->addMessageTranslated('danger', 'AUTH.NOT_FOUND');
            $error = true;
        }

        // Check if the user already has a role for this seeker
        if ($user->roleForSeeker($args['seeker'], $args['id'])) {
            $ms->addMessageTranslated('danger', 'AUTH.USER_HAS_ROLE');
            $error = true;
        }

        if ($error) {
            return $response->withStatus(400);
        }

        // All checks passed!  log events/activities and create role
        // Begin transaction - DB will be rolled back if an exception occurs
        Capsule::transaction( function() use ($classMapper, $data, $ms, $args, $currentUser, $user, $role) {

            // Assign the role
            $user->seeker($args['seeker'])->sync([
                $args['id'] => ['role_id' => $role->id]
            ]);

            // Create activity record
            /*$this->ci->userActivityLogger->info("User {$currentUser->user_name} created role {$role->name}.", [
                'type' => 'role_create',
                'user_id' => $currentUser->id
            ]);*/
        });

        $ms->addMessageTranslated('success', 'AUTH.CREATED', [
            'role_name' => $translator->translate($role->name),
            'user_name' => $user->user_name
        ]);

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

        /** @var UserFrosting\Sprinkle\Account\Database\Models\User $currentUser */
        $currentUser = $this->ci->currentUser;

        /** @var MessageStream $ms */
        $ms = $this->ci->alerts;

        // Get the role
        if (!$auth = $classMapper->staticMethod('altAuth', 'find', $args['id']))
        {
            $e = new HttpException("Alt Authorization role '{$args['id']}' does not exist.");
            $e->addUserMessage("AUTH.NOT_FOUND");
            throw $e;
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
        $form = new Form($schema);

        // Fill in the roles
        $possibleRoles = $classMapper->staticMethod('altRole', 'where', 'seeker', $auth->seeker_type)->get();

        // Create the select association. Don't need to translate, FormGenerator does it automatically
        $roleSelect = $possibleRoles->pluck('name', 'id');

        // We pass the role as the option of the selects
        $form->setInputArgument("role", "options", $roleSelect);

        // We need to the the value manually. If we pass the relation, it will associate the relation
        // as the value of the select
        $form->setValue("role", $auth->role_id);

        return $this->ci->view->render($response, 'FormGenerator/modal.html.twig', [
            "box_id" => $params['box_id'],
            "box_title" => "ROLE.EDIT",
            "form_action" => $this->ci->get('router')->pathFor('api.auth.edit', [
                'id' => $args['id']
            ]),
            "form_method" => "PUT",
            "fields" => $form->generate(),
            "validators" => $validator->rules('json', true)
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
        // Get PUT parameters: (name, slug, description)
        $params = $request->getParsedBody();

        /** @var UserFrosting\Sprinkle\Account\Authorize\AuthorizationManager */
        $authorizer = $this->ci->authorizer;

        /** @var UserFrosting\Sprinkle\Account\Database\Models\User $currentUser */
        $currentUser = $this->ci->currentUser;

        /** @var UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = $this->ci->classMapper;

        /** @var UserFrosting\I18n\MessageTranslator $translator */
        $translator = $this->ci->translator;

        /** @var MessageStream $ms */
        $ms = $this->ci->alerts;

        // Access-controlled resource - check that currentUser has permission to edit submitted fields for this role
        // !TODO
        /*if (!$authorizer->checkAccess($currentUser, 'update_role_field', [
            'role' => $role,
            'fields' => array_values(array_unique($fieldNames))
        ])) {
            throw new ForbiddenException();
        }*/

        // Get the auth data from the id in the route
        if (!$auth = $classMapper->staticMethod('altAuth', 'find', $args['id']))
        {
            $e = new HttpException("Alt Authorization role '{$args['id']}' does not exist.");
            $e->addUserMessage("AUTH.NOT_FOUND");
            throw $e;
        }

        // We won't require the schema here. We (should) know we have something
        // Plus, schema or not, we do need to check the role exist manually
        $newRole = $classMapper->staticMethod('altRole', 'find', $params['role']);

        // 1° Check it exist
        if (!$newRole) {
            $ms->addMessageTranslated('danger', 'AUTH.NOT_FOUND');
            return $response->withStatus(400);
        }

        // 2° Make sure the role is for the same seeker
        if ($newRole->seeker != $auth->seeker_type) {
            $ms->addMessageTranslated('danger', 'AUTH.BAD_SEEKER');
            return $response->withStatus(400);
        }

        // Begin transaction - DB will be rolled back if an exception occurs
        Capsule::transaction( function() use ($currentUser, $auth, $newRole) {
            // Update the role and generate success messages
            // We are allowed to change the `auth` relation directly for the role
            // (It's expected to change for a given user/seeker combo)
            // The sync method on the user could also have been used
            $auth->role_id = $newRole->id;
            $auth->save();

            // Create activity record
            /*$this->ci->userActivityLogger->info("User {$currentUser->user_name} defined the role {$newRole->name} for user....", [
                'type' => 'role_update_info',
                'user_id' => $currentUser->id
            ]);*/
        });

        $ms->addMessageTranslated('success', 'AUTH.UPDATED', [
            'role_name' => $translator->translate($newRole->name),
            'user_name' => $auth->user->user_name
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

        /** @var UserFrosting\Sprinkle\Account\Database\Models\User $currentUser */
        $currentUser = $this->ci->currentUser;

        /** @var UserFrosting\Sprinkle\Core\Util\ClassMapper $classMapper */
        $classMapper = $this->ci->classMapper;

        /** @var UserFrosting\I18n\MessageTranslator $translator */
        $translator = $this->ci->translator;

        /** @var MessageStream $ms */
        $ms = $this->ci->alerts;

        // Get the role
        if (!$auth = $classMapper->staticMethod('altAuth', 'find', $args['id']))
        {
            $e = new HttpException("Alt Authorization role '{$args['id']}' does not exist.");
            $e->addUserMessage("AUTH.NOT_FOUND");
            throw $e;
        }

        // Access-controlled page
        /*if (!$authorizer->checkAccess($currentUser, 'delete_role', [
            'role' => $role
        ])) {
            throw new ForbiddenException();
        }*/

        // Begin transaction - DB will be rolled back if an exception occurs
        Capsule::transaction( function() use ($auth, $currentUser) {

            $auth->delete();

            // Create activity record
            /*$this->ci->userActivityLogger->info("User {$currentUser->user_name} deleted role {$roleName}.", [
                'type' => 'role_delete',
                'user_id' => $currentUser->id
            ]);*/
        });

        $ms->addMessageTranslated('success', 'AUTH.DELETED', [
            'role_name' => $translator->translate($auth->role->name),
            'user_name' => $auth->user->user_name
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

        /** @var UserFrosting\Sprinkle\Account\Database\Models\User $currentUser */
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
