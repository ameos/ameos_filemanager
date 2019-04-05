<?php


return [
    'filemanager_folder_getid' => [
        'path' => '/filemanager/folder/getid',
        'target' => \Ameos\AmeosFilemanager\Controller\Backend\AjaxController::class . '::getFolderId'
    ]
];
