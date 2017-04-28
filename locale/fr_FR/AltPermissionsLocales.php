<?php

 /**
 * UF AltPermissions
 *
 * en_US
 *
 * US English message token translations for the 'AltPermissions' sprinkle.
 *
 * @link      https://github.com/lcharette/UF-AltPermissions
 * @copyright Copyright (c) 2016 Louis Charette
 * @license   https://github.com/lcharette/UF-AltPermissions/blob/master/licenses/UserFrosting.md (MIT License)
 */

return [

    "ALT_ROLE" => [
        "PAGE_DESCRIPTION"  => "Une liste des rôles pour le demandeur <em>{{seeker}}</em>. Fournit des outils de gestion pour l'édition et la suppression de rôles pour ce chercheur.",
        "PAGE_TITLE" => "Rôles pour {{seeker}}",

        "USERS" => "Utilisateurs du rôle",

        "DEFAULT" => [
            "@TRANSLATION" => "Rôle par défaut",
            "CONFIRM" => "Êtes-vous sûr de vouloir définir ce rôle comme rôle par défaut? L'utilisateur sans rôle héritera de ce rôle.",
            "CONFIRM_UNSET" => "Êtes-vous sûr de vouloir supprimer l'état de rôle par défaut pour le rôle sélectionné? L'utilisateur sans rôle n'aura aucune autotisation (toutes les autorisations sont désactivées).",
            "UPDATED" => "Le rôle <strong>{{role_name}}</strong> défini par défaut pour le demandeur <em>{{seeker}}</em>",
            "UPDATED_UNSET" => "Le rôle <strong>{{role_name}}</strong> retiré comme rôle par défaut pour le demandeur <em>{{seeker}}</em>",
            "SET" => "Définir comme rôle par défaut",
            "UNSET" => "Retirer le rôle par défaut"
        ]
    ],

    "AUTH" => [
        "BAD_SEEKER" => "Le demandeur de rôle sélectionné n'est pas valide",
        "NOT_FOUND" => "Le rôle sélectionné n'existe pas",

        "CREATED" => "<strong>{{user_name}}</strong> ajouté avec succès avec le rôle <strong>{{role_name}}</strong>",
        "UPDATED" => "Rôle <strong>{{role_name}}</strong> défini pour <strong>{{user_name}}</strong>",
        "DELETED" => "Rôle <strong>{{role_name}}</strong> retiré pour <strong>{{user_name}}</strong>",

        "ADD_USER" => "Ajouter {{&USER}}",
        "SELECT_USER" => "Choisir {{&USER}}",
        "USER_HAS_ROLE" => "L'{{&USER}} sélectionné possède déjà un rôle défini pour ce demandeur"
    ]
];
