<?php

return [

    'attribute' => [
        'calculated' => '计算属性',
    ],

    'formula' => [
        'title'            => '公式',
        'label'            => '公式',
        'placeholder'      => "concat('-', manufacturer, manufacturer_part_number)",
        'info'             => '每次展示商品、导出或通过 API 返回时，该值都会由此公式推导得出。它不会被存储，也无法导入。',
        'reference-hint'   => '通过代码引用其他属性。',
        'available'        => '可用函数',
        'examples'         => '示例',
        'example-concat'   => '连接两个属性，跳过为空的属性',
        'example-category' => '用 # 连接的分类标签路径',
        'example-fallback' => '当第一个属性为空时退回到第二个属性',
        'read-only'        => '计算属性',
        'read-only-hint'   => '该值由其他属性计算得出，无法编辑。',

        'variables' => [
            'title'      => '可用变量',
            'sku'        => '商品的 SKU',
            'type'       => '商品类型',
            'status'     => '商品状态',
            'family'     => '属性族代码',
            'parent'     => '变体的父商品 SKU',
            'categories' => '已分配的分类代码',
            'channel'    => '正在渲染的渠道',
            'locale'     => '正在渲染的语言',
        ],

        'errors' => [
            'syntax'              => '无法解析公式：:detail',
            'unknown-variable'    => '公式中存在未知属性或变量“:name”。',
            'disallowed-function' => '公式中不允许使用函数“:name”。',
            'disallowed-operator' => '公式中不允许使用运算符“:name”。',
            'reserved-variable'   => '名称“:name”为保留名称，不能在公式中使用。',
            'too-long'            => '公式长度不能超过 :max 个字符。',
            'empty'               => '计算属性需要一个公式。',
            'circular'            => '该公式造成了循环依赖：:cycle',
            'referenced-by'       => '无法删除该属性，因为以下属性的公式正在使用它：:attributes',
        ],
    ],

];
