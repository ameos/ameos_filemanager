<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "ameos_filemanager".
 *
 * Auto generated 21-03-2016 14:15
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title'            => 'File manager',
    'description'      => 'This will allow you to fully handle file upload in FE context',
    'category'         => 'plugin',
    'author'           => 'Ameos Team',
    'author_company'   => 'Ameos',
    'author_email'     => 'typo3dev@ameos.com',  
    'state'            => 'beta',
    'uploadfolder'     => false,
    'createDirs'       => '',
    'clearCacheOnLoad' => 0,
    'version'          => '1.1.7',
    'clearcacheonload' => false,
    'constraints'      => [
        'depends'   => ['typo3' => '7.6.0-8.7.99', 'php' => '5.5.0-7.0.99'],
        'conflicts' => [],
        'suggests'  => [],
    ],
];

