<?php

return [

    'attribute' => [
        'measurement' => 'Mesure',
    ],

    'measurement' => [
        'index' => [
            'create'   => 'Créer une famille de mesures',
            'code'     => 'Code',
            'standard' => 'Code de l’unité standard',
            'symbol'   => 'Symbole',
            'save'     => 'Enregistrer',
        ],

        'edit' => [
            'measurement_edit' => 'Modifier la famille de mesures',
            'back'             => 'Retour',
            'save'             => 'Enregistrer',
            'general'          => 'Général',
            'code'             => 'Code',
            'label'            => 'Libellé',
            'units'            => 'Unités',
            'create_units'     => 'Créer des unités',
        ],

        'unit' => [
            'edit_unit'   => 'Modifier l’unité',
            'create_unit' => 'Créer une unité',
            'symbol'      => 'Symbole',
            'save'        => 'Enregistrer',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => 'Familles de mesures',
        'measurement_family'   => 'Famille de mesures',
        'measurement_unit'     => 'Unité de mesure',
    ],

    'datagrid' => [
        'labels'        => 'Libellés',
        'code'          => 'Code',
        'standard_unit' => 'Unité standard',
        'unit_count'    => 'Nombre d’unités',
        'is_standard'   => 'Marquer comme unité standard',
    ],

    'messages' => [
        'family' => [
            'updated'      => 'La famille de mesures a été mise à jour avec succès.',
            'deleted'      => 'La famille de mesures a été supprimée avec succès.',
            'mass_deleted' => 'Les familles de mesures sélectionnées ont été supprimées avec succès.',
        ],

        'unit' => [
            'not_found'         => 'Famille de mesures introuvable.',
            'already_exists'    => 'Le code de l’unité existe déjà.',
            'not_foundd'        => 'Unité introuvable.',
            'deleted'           => 'L’unité a été supprimée avec succès.',
            'no_items_selected' => 'Aucun élément sélectionné.',
            'mass_deleted'      => 'Les unités de mesure sélectionnées ont été supprimées avec succès.',
        ],
    ],

];
