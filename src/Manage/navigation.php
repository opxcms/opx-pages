<?php

return [
    'items' => [
        'pages' => [
            'caption' => 'opx_pages::manage.pages',
            'route' => 'opx_pages::pages_list',
            'section' => 'system/site',
            'permission' => 'opx_pages::list',
        ],
    ],

    'routes' => [
        'opx_pages::pages_list' => [
            'route' => '/pages',
            'loader' => 'manage/api/module/opx_pages/pages_list',
        ],
        'opx_pages::pages_add' => [
            'route' => '/pages/add',
            'loader' => 'manage/api/module/opx_pages/pages_edit/add',
        ],
        'opx_pages::pages_edit' => [
            'route' => '/pages/edit/:id',
            'loader' => 'manage/api/module/opx_pages/pages_edit/edit',
        ],
    ]
];