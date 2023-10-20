<?php

use Ameos\AmeosFilemanager\Controller\Backend\ExportController;

return [
    'filemanager_export' => [
        'parent' => 'file',
        'position' => ['bottom'],
        'access' => 'user, group',
        'workspaces' => 'live',
        'path' => '/module/file/filemanager/export',
        'iconIdentifier' => 'extension-ameosfilemanager-moduleexport',
        'labels' => 'LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_modexport.xlf',
        'routes' => [
            '_default' => [
                'target' => ExportController::class . '::handleRequest',
            ],
        ],
    ],
];
