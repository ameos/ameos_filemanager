<?php

use Ameos\AmeosFilemanager\Controller\Backend\AjaxController;

return [
    'filemanager_folder_getid' => [
        'path' => '/filemanager/folder/getid',
        'target' => AjaxController::class . '::getFolderId',
    ],
];
