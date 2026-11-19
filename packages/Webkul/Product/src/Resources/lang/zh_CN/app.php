<?php

return [
    'acl' => [
        'variant-structures' => '变体结构',
    ],

    'datagrid' => [
        'copy-of-slug'                  => '副本-:value',
        'copy-of'                       => '副本 :value',
        'variant-already-exist-message' => '具有相同属性选项的变体已存在。',
    ],

    'response' => [
        'product-can-not-be-copied' => ':type 类型的产品无法复制',
    ],

    'type'     => [
        'configurable'  => '可配置',
        'simple'        => '简单',
        'variant-group' => '变体组',
        'variant_group' => '变体组',
    ],

    'templates' => [
        'messages' => [
            'circular-reference'      => '检测到模板循环引用：:path',
            'currency-not-allowed'    => '只有价格属性可以接受货币，但 ":attribute" 不是价格属性。',
            'currency-not-enabled'    => '模板中使用的货币 ":currency" 未在任何频道中启用。',
            'currency-not-in-channel' => '货币 ":currency" 未在频道 ":channel" 中启用。',
            'filter-failed'           => '无法将筛选器 ":filter" 应用于 ":attribute" 的值。',
            'filter-not-supported'    => '筛选器 ":filter" 不能应用于属性 ":attribute"。',
            'format-date-position'    => 'format_date 筛选器必须是 ":expression" 中的第一个筛选器。',
            'inside-html-tag'         => '模板表达式不能放在 HTML 标签或属性（例如链接和图片）内。',
            'invalid-filter-argument' => '":expression" 中的筛选器 ":filter" 缺少参数或参数无效。',
            'invalid-reference'       => '存储在 ":attribute" 中的模板无效，无法渲染。',
            'invalid-syntax'          => '模板表达式 ":expression" 无效。请使用属性代码，后跟可选的筛选器，例如：name | upper',
            'max-depth'               => '模板引用在 ":attribute" 处的嵌套层级超过 :max 层。',
            'missing-value'           => '属性 ":attribute" 在此频道和地区下没有值。',
            'not-in-family'           => '模板中使用的属性 ":attribute" 不属于该产品的属性族。',
            'output-too-large'        => '渲染后的模板超过 :max 字节，已被截断。',
            'self-reference'          => '模板不能引用其自身的属性 ":attribute"。',
            'single-brace'            => '发现单花括号中的 ":text"。模板表达式需要使用双花括号。',
            'source-too-long'         => '模板最多只能有 :max 字节。',
            'too-complex'             => '模板过于复杂，无法检查。请缩短或简化模板。',
            'too-many-expressions'    => '一个模板最多只能包含 :max 个表达式。',
            'too-many-filters'        => '模板表达式 ":expression" 最多只能使用 :max 个筛选器。',
            'unclosed-expression'     => '模板表达式已打开但未关闭。',
            'unknown-attribute'       => '模板中使用的属性 ":attribute" 不存在。',
            'unknown-currency'        => '模板中使用的货币 ":currency" 不存在。',
            'unknown-filter'          => '不支持筛选器 ":filter"。可用的筛选器：:filters',
            'unsupported-attribute'   => '属性 ":attribute" 的类型不受支持，因此不能在模板中使用。',
        ],
    ],
];
