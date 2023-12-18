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

$EM_CONF['ameos_filemanager'] = [
    'title'            => 'File manager',
    'description'      => 'This will allow you to fully handle file upload in FE context',
    'category'         => 'plugin',
    'author'           => 'Ameos Team',
    'author_company'   => 'Ameos',
    'author_email'     => 'typo3dev@ameos.com',
    'state'            => 'beta',
    'version'          => '3.0.0',
    'constraints'      => [
        'depends' => [
            'typo3' => '12.4.0-12.4.99',
            'php'   => '8.0.0-8.2.99',
        ],
        'conflicts' => [],
        'suggests'  => [],
    ],
];
