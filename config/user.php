<?php
return [
    /*
     *Default user enforcer
     */
    'default' => 'basic',

    'log' => [
        'enabled' => false,
        //'logger' => rrbrr\Log::class,
    ],

    'enforcers' => [
        'basic' => [
            /*
            * Model 设置
            */
            'model' => [
                'config_type' => 'file',
                'config_text' => '',
            ],

            // 适配器 .
            'adapter' => user\adapter\DatabaseAdapter::class,

            /*
            * 数据库设置.
            */
            'database' => [
                // 数据库连接名称，不填为默认配置.
                'connection' => '',
                'table_name' => 'user', //用户名表
            ],
        ],
    ],
    'template_root_dir' => "../../vendor/rrbrr/user/src/user/view" //模板根目录
];