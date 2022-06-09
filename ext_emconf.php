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
    'version'          => '2.0.3',
    'autoload'         => ['psr-4' => ['Ameos\\AmeosFilemanager\\' => 'Classes']],
    'constraints'      => [
        'depends' => [
            'typo3' => '10.4.0-11.5.99',
            'php'   => '7.1.0-7.4.99',
        ],
        'conflicts' => [],
        'suggests'  => [],
    ],
];
