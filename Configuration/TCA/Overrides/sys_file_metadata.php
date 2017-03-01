<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$ll = 'LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_db.xlf:tx_ameosfilemanager_domain_model_file';

$additionalColumnsMetadata = [
    'fe_group_read' => [
        'exclude' => 1,
        'label'   => $ll . '.fe_groups_read',
        'config'  => [
            'type'                => 'select',
            'size'                => 5,
            'maxitems'            => 20,
            'exclusiveKeys'       => '-1,-2',
            'foreign_table'       => 'fe_groups',
            'foreign_table_where' => 'ORDER BY fe_groups.title',
            'items'               => [
                ['LLL:EXT:lang/locallang_general.xlf:LGL.any_login',  -2],
                ['LLL:EXT:lang/locallang_general.xlf:LGL.usergroups', '--div--']
            ],
            
        ]
    ],
    'keywords' => [      
            'exclude' => 1,   
            'label'   => $ll . '.keywords',     
            'config'  => [
                'type' => 'text', 
                'cols' => '15',
                'rows' => '5', 
                'eval' => 'trim', 
            ]
        ],
    'fe_group_write' => [
        'exclude'    => 1,
        'label'      => $ll . '.fe_groups_write',
        'config'     => [
            'type'                => 'select',
            'size'                => 5,
            'maxitems'            => 20,
            'exclusiveKeys'       => '-1,-2',
            'foreign_table'       => 'fe_groups',
            'foreign_table_where' => 'ORDER BY fe_groups.title',
            'items'               => [
                ['LLL:EXT:lang/locallang_general.xlf:LGL.any_login',  -2],
                ['LLL:EXT:lang/locallang_general.xlf:LGL.usergroups', '--div--']
            ],
        ]
    ],
    'no_read_access' => [
        'exclude' => 1, 
        'label'   => $ll . '.no_read_access',
        'config'  => [
            'type'    => 'check',
            'default' => '0',
        ]
    ],
    'no_write_access' => [
        'exclude' => 1, 
        'label'   => $ll . '.no_write_access',
        'config'  => [
            'type'    => 'check',
            'default' => '0',
        ]
    ],
    'owner_read_only' => [
        'exclude' => 1, 
        'label'   => $ll . '.owner_read_only',
        'config'  => [
            'type'    => 'check',
            'default' => '0',
        ]
    ],
    'fe_user_id' => [
        'exclude' => 1,
        'label'   => $ll . '.fe_user_id',
        'config'  => [
            'type'          => 'select',
            'maxitems'      => 1,
            'items'         => [['', 0]],
            'size'          => 1,
            'foreign_table' => 'fe_users',
        ]
    ],
    'folder_uid' => [
        'exclude' => 1,
        'label'   => $ll . '.folder_uid',
        'config'  => [
            'type'          => 'select',
            'maxitems'      => 1,
            'items'         => [['', 0]],
            'size'          => 1,
            'foreign_table' => 'tx_ameosfilemanager_domain_model_folder',
        ]
    ],
    'datetime' => [
        'exclude' => 1, 
        'label'   => $ll . '.datetime',
        'config'  => [
            'type'     => 'input',
            'size'     => '8',
            'max'      => '20',
            'eval'     => 'datetime',
            'checkbox' => '0',
            'default'  => '0',
        ]
    ],
    'status' => [
        'exclude' => 1,
        'label'   => $ll . '.status',
        'config'  => [
            'type'          => 'select',
            'maxitems'      => 1,
            'minitems'      => 0,
            'size'          => 1,
            'items'         => [
                [$ll . '.status.parent',  0],
                [$ll . '.status.ready',   1],
                [$ll . '.status.archive', 2],
            ],
        ]
    ],
    'realstatus' => ['config' => ['type' => 'passthrough']]
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('sys_file_metadata', $additionalColumnsMetadata);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('sys_file_metadata', '--div--;LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_db.xlf:tx_ameosfilemanager,datetime,no_read_access,fe_group_read, no_write_access,owner_read_only,fe_group_write,keywords,fe_user_id,status');

