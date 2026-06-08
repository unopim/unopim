<?php

return [

    'acl' => [
        'unauthorized' => 'У вас нет прав для выполнения этого действия.',
    ],
    'attribute' => [
        'measurement' => 'Измерение',
    ],

    'measurement' => [
        'index' => [
            'create'                => 'Создать группу измерений',
            'code'                  => 'Код',
            'standard'              => 'Код стандартной единицы',
            'symbol'                => 'Символ',
            'save'                  => 'Сохранить',
            'conversion_operation'  => 'Conversion operation',
            'add_new_operation'     => 'Add New Operation',
        ],

        'edit' => [
            'measurement_edit'      => 'Редактировать группу измерений',
            'back'                  => 'Назад',
            'save'                  => 'Сохранить',
            'conversion_operation'  => 'Conversion operation',
            'add_new_operation'     => 'Add New Operation',
            'general'               => 'Общее',
            'code'                  => 'Код',
            'label'                 => 'Метка',
            'units'                 => 'Единицы',
            'create_units'          => 'Создать единицы',
        ],

        'unit' => [
            'edit_unit'             => 'Редактировать единицу',
            'create_unit'           => 'Создать единицу',
            'symbol'                => 'Символ',
            'save'                  => 'Сохранить',
            'conversion_operation'  => 'Операция преобразования',
            'add_new_operation'     => 'Добавить новую операцию',
            'conversion_value'      => 'Значение',
            'conversion_operator'   => 'Оператор',
        ],
    ],

    'attribute_type' => [
        'measurement_families' => 'Группы измерений',
        'measurement_family'   => 'Группа измерений',
        'measurement_unit'     => 'Единица измерения',
    ],

    'datagrid' => [
        'labels'        => 'Название',
        'code'          => 'Код',
        'standard_unit' => 'Стандартная единица',
        'unit_count'    => 'Количество единиц',
        'is_standard'   => 'Отметить как стандартную единицу',
    ],

    'importers' => [
        'products' => [
            'validation' => [
                'invalid-unit' => 'Единица ":unit" не является допустимой единицей для атрибута измерения ":attribute".',
            ],
        ],
    ],

    'messages' => [
        'family' => [
            'created'      => 'Семейство измерений успешно создано.',
            'updated'      => 'Группа измерений успешно обновлена.',
            'deleted'      => 'Группа измерений успешно удалена.',
            'mass_deleted' => 'Выбранные группы измерений успешно удалены.',
        ],

        'unit' => [
            'not_found'              => 'Группа измерений не найдена.',
            'already_exists'         => 'Код единицы уже существует.',
            'units_not_found'        => 'Единица не найдена.',
            'deleted'                => 'Единица успешно удалена.',
            'no_items_selected'      => 'Элементы не выбраны.',
            'mass_deleted'           => 'Выбранные единицы измерения успешно удалены.',
        ],
    ],

];
