<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$ll = 'LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_db.xlf:tx_ameosfilemanager_domain_model_folder';
$corell = version_compare(\TYPO3\CMS\Core\Utility\VersionNumberUtility::getCurrentTypo3Version(), '9.0.0', '>=')
    ? 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf'
    : 'LLL:EXT:lang/locallang_general.xlf';


$GLOBALS['TCA']['tx_ameosfilemanager_domain_model_folder'] = [
    'ctrl' => [
        'title'          => $ll,
        'label'          => 'title', 
        'tstamp'         => 'tstamp',
        'crdate'         => 'crdate',
        'cruser_id'      => 'cruser_id',
        'delete'         => 'deleted',
        'enablecolumns'  => ['disabled' => 'hidden', 'fe_group' => 'fe_group_read'],
        'hideTable'      => true,
        'default_sortby' => 'ORDER BY crdate',
        'iconfile'       => ExtensionManagementUtility::extPath('ameos_filemanager') . 'Resources/Public/IconsBackend/folder.svg',
        'searchFields'   => 'title, description, keywords',
        'rootLevel'      => 1,
        'security'       => ['ignoreRootLevelRestriction' => 1, 'ignoreWebMountRestriction' => 1],        
    ],
    'palettes' => [
        'owner' => ['showitem' => 'fe_user_id,--linebreak--,owner_has_read_access,no_read_access,owner_has_write_access,no_write_access'],
    ],
    'types' => ['0' => [
        'showitem' => 'description,keywords,
            --div--;' . $ll . '.accessright,--palette--;;owner,fe_group_read,fe_group_write,fe_group_addfolder,fe_group_addfile'
    ]],
    'interface' => ['showRecordFieldList' => 'title,description,keywords'],
    'columns' => [
        'hidden' => [        
            'exclude' => 1,
            'label'   => $corell . ':LGL.hidden',
            'config'  => ['type' => 'check', 'default' => '0']
        ],
        'crdate' => [
            'exclude' => 1, 
            'label'   => $corell . ':LGL.crdate',
            'config'  => ['type' => 'input']
        ],
        'tstamp' => [
            'exclude' => 1, 
            'label'   => $corell . ':LGL.tstamp',
            'config'  => ['type' => 'input']
        ],
        'cruser_id' => [
            'exclude' => 1,
            'label'   => $corell . ':LGL.be_user',
            'config'  => [
                'type'                => 'select',
                'renderType'          => 'selectSingleBox',
                'size'                => 5,
                'maxitems'            => 1,
                'foreign_table'       => 'be_user',
                'foreign_table_where' => 'ORDER BY be_user.uid'
            ]
        ],
        'fe_user_id' => [
            'exclude' => 1,
            'label'   => $ll . '.fe_user_id',
            'config'  => [
                'type'          => 'group',
                'internal_type' => 'db',
                'allowed'       => 'fe_users',
                'maxitems'      => 1,
                'size'          => 1,
                'wizards'       => ['suggest' => ['type' => 'suggest']],
            ]
        ],
        'title' => [
            'exclude' => 1, 
            'label' => $ll . '.title',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            ]
        ],
        'no_read_access' => [
            'exclude' => 1, 
            'label' => $ll . '.no_read_access',
            'config' => [
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
        'owner_has_read_access' => [
            'exclude' => 1,
            'label'   => $ll . '.owner_has_read_access',
            'config'  => [
                'type'    => 'check',
                'default' => '1'
            ]
        ],
        'owner_has_write_access' => [
            'exclude' => 1,
            'label'   => $ll . '.owner_has_write_access',
            'config'  => [
                'type'    => 'check',
                'default' => '1'
            ]
        ],
        'description' => [      
            'exclude' => 1,   
            'label'   => $ll . '.description',     
            'config'  => [
                'type' => 'text', 
                'cols' => '15',
                'rows' => '5', 
                'eval' => 'trim', 
            ]
        ],
        'identifier' => [      
            'exclude' => 1,   
            'label'   => $ll . '.identifier',
            'config'  => [
                'type' => 'text',
                'cols' => '15',
                'rows' => '5',
                'eval' => 'trim', 
            ]
        ],
        'storage' => [
            'exclude' => 1,
            'label'   => $corell . ':sys_file.storage',
            'config'  => [
                'type' => 'input',
            ]
        ],
        'keywords' => [      
            'exclude' => 1,   
            'label'   => $ll . '.keywords',     
            'config'  => [
                'type' => 'text', 
                'cols' => '40',
                'rows' => '3', 
                'eval' => 'trim', 
            ]
        ],
        'fe_group_read' => [
            'exclude' => 1,
            'label'   => $ll . '.fe_group_read',
            'config'  => [
                'type'       => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size'       => 5,
                'maxitems'   => 20,
                'items'      => [
                    [$corell . ':LGL.any_login',  -2],
                    [$corell . ':LGL.usergroups', '--div--']
                ],
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title'
            ]
        ],
        'fe_group_write' => [
            'exclude' => 1,
            'label'   => $ll . '.fe_group_write',
            'config'  => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 5,
                'maxitems' => 20,
                'items'      => [
                    [$corell . ':LGL.any_login',  -2],
                    [$corell . ':LGL.usergroups', '--div--']
                ],
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title'
            ]
        ],
        'fe_group_addfile' => [
            'exclude' => 1,
            'label'   => $ll . '.fe_group_addfile',
            'config'  => [
                'type'       => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size'       => 5,
                'maxitems'   => 20,
                'items'      => [
                    [$corell . ':LGL.any_login',  -2],
                    [$corell . ':LGL.usergroups', '--div--']
                ],
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title'
            ]
        ],
        'fe_group_addfolder' => [
            'exclude' => 1,
            'label'   => $ll . '.fe_group_addfolder',
            'config'  => [
                'type'       => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size'       => 5,
                'maxitems'   => 20,
                'items'      => [
                    [$corell . ':LGL.any_login',  -2],
                    [$corell . ':LGL.usergroups', '--div--']
                ],
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title'
            ]
        ],
        'folders' => [ 
            'exclude' => 1,
            'label'   => $ll . '.folders',
            'config'  => [
                'maxitems'      => 500,
                'type'          => 'inline',
                'foreign_table' => 'tx_ameosfilemanager_domain_model_folder',
                'foreign_field' => 'uid_parent',
                'appearance'    => ['collapseAll' => 1],
            ]
        ],
        'uid_parent' => [
            'exclude' => 1,
            'label'   => $ll . '.uid_parent',
            'config'  => [
                'type'                => 'select',
                'renderType'          => 'selectSingleBox',
                'size'                => 5,
                'maxitems'            => 1,
                'foreign_table'       => 'tx_ameosfilemanager_domain_model_folder',
                'foreign_table_where' => 'ORDER BY tx_ameosfilemanager_domain_model_folder.title'
            ]
        ],
        'files' => [
            'exclude' => 1,
            'label'   => $ll . '.files',
            'config'  => [
                'maxitems'      => 500,
                'type'          => 'inline',
                'foreign_table' => 'sys_file_metadata',
                'foreign_field' => 'folder_uid',
            ]
        ],
    ],

];

ExtensionManagementUtility::makeCategorizable('ameos_filemanager', 'tx_ameosfilemanager_domain_model_folder', 'cats', ['exclude' => FALSE]);
