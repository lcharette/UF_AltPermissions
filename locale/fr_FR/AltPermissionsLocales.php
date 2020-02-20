<?php

/*
 * UF AltPermissions Sprinkle
 *
 * @author    Louis Charette
 * @copyright Copyright (c) 2018 Louis Charette
 * @link      https://github.com/lcharette/UF_AltPermissions
 * @license   https://github.com/lcharette/UF_AltPermissions/blob/master/LICENSE.md (MIT License)
 */

return [

    'ALT_ROLE' => [
        'PAGE_DESCRIPTION'  => "Une liste des rôles pour le demandeur <em>{{seeker}}</em>. Fournit des outils de gestion pour l'édition et la suppression de rôles pour ce chercheur.",
        'PAGE_TITLE'        => 'Rôles pour {{seeker}}',

        'USERS' => 'Utilisateurs du rôle',
    ],

    'AUTH' => [
        'BAD_SEEKER' => "Le demandeur de rôle sélectionné n'est pas valide",
        'NOT_FOUND'  => "Le rôle sélectionné n'existe pas",

        'CREATED' => '<strong>{{user_name}}</strong> ajouté avec succès avec le rôle <strong>{{role_name}}</strong>',
        'UPDATED' => 'Rôle <strong>{{role_name}}</strong> défini pour <strong>{{user_name}}</strong>',
        'DELETED' => 'Rôle <strong>{{role_name}}</strong> retiré pour <strong>{{user_name}}</strong>',

        'ADD_USER'      => 'Ajouter {{&USER}}',
        'SELECT_USER'   => 'Choisir {{&USER}}',
        'USER_HAS_ROLE' => "L'{{&USER}} sélectionné possède déjà un rôle défini pour ce demandeur",
    ],
];
