<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die('Access denied');

$ll = 'LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_db.xlf:tx_ameosfilemanager_domain_model_file';
$corell = 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf';

$additionalColumnsMetadata = [
    'fe_group_read' => [
        'exclude' => 1,
        'label'   => $ll . '.fe_groups_read',
        'config'  => [
            'type'                => 'select',
            'renderType'          => 'selectMultipleSideBySide',
            'size'                => 5,
            'maxitems'            => 20,
            'exclusiveKeys'       => '-1,-2',
            'foreign_table'       => 'fe_groups',
            'foreign_table_where' => 'ORDER BY fe_groups.title',
            'items'               => [
                [
                    'label' => $corell . ':LGL.any_login',
                    'value' => -2
                ],
                [
                    'label' => $corell . ':LGL.usergroups',
                    'value' => '--div--'
                ],
            ],
        ],
    ],
    'keywords' => [
            'exclude' => 1,
            'label'   => $ll . '.keywords',
            'config'  => [
                'type' => 'text',
                'cols' => '40',
                'rows' => '3',
                'eval' => 'trim',
            ],
        ],
    'fe_group_write' => [
        'exclude'    => 1,
        'label'      => $ll . '.fe_groups_write',
        'config'     => [
            'type'                => 'select',
            'renderType'          => 'selectMultipleSideBySide',
            'size'                => 5,
            'maxitems'            => 20,
            'exclusiveKeys'       => '-1,-2',
            'foreign_table'       => 'fe_groups',
            'foreign_table_where' => 'ORDER BY fe_groups.title',
            'items'               => [
                [
                    'label' => $corell . ':LGL.any_login',
                    'value' => -2
                ],
                [
                    'label' => $corell . ':LGL.usergroups',
                    'value' => '--div--'
                ],
            ],
        ],
    ],
    'no_read_access' => [
        'exclude' => 1,
        'label'   => $ll . '.no_read_access',
        'config'  => [
            'type'    => 'check',
            'default' => '0',
        ],
    ],
    'no_write_access' => [
        'exclude' => 1,
        'label'   => $ll . '.no_write_access',
        'config'  => [
            'type'    => 'check',
            'default' => '0',
        ],
    ],
    'owner_read_only' => [
        'exclude' => 1,
        'label'   => $ll . '.owner_read_only',
        'config'  => [
            'type'    => 'check',
            'default' => '0',
        ],
    ],
    'owner_has_read_access' => [
        'exclude' => 1,
        'label'   => $ll . '.owner_has_read_access',
        'config'  => [
            'type'    => 'check',
            'default' => '1',
        ],
    ],
    'owner_has_write_access' => [
        'exclude' => 1,
        'label'   => $ll . '.owner_has_write_access',
        'config'  => [
            'type'    => 'check',
            'default' => '1',
        ],
    ],
    'fe_user_id' => [
        'exclude' => 1,
        'label'   => $ll . '.fe_user_id',
        'config'  => [
            'type'          => 'group',
            'allowed'       => 'fe_users',
            'maxitems'      => 1,
            'size'          => 1,
        ],
    ],
    'folder_uid' => [
        'exclude' => 1,
        'label'   => $ll . '.folder_uid',
        'config'  => [
            'type'          => 'select',
            'renderType'    => 'selectSingle',
            'maxitems'      => 1,
            'items'         => [['label' => '', 'value' => 0]],
            'size'          => 1,
            'foreign_table' => 'tx_ameosfilemanager_domain_model_folder',
        ],
    ],
];

$GLOBALS['TCA']['sys_file_metadata']['palettes']['owner'] = [
    'showitem' => 'fe_user_id,--linebreak--,owner_has_read_access,
        no_read_access,owner_has_write_access,no_write_access',
];

ExtensionManagementUtility::addTCAcolumns('sys_file_metadata', $additionalColumnsMetadata);
ExtensionManagementUtility::addToAllTCAtypes(
    'sys_file_metadata',
    '--div--;' . $ll . '.accessright,--palette--;;owner,fe_group_read,fe_group_write'
);
ExtensionManagementUtility::addToAllTCAtypes(
    'sys_file_metadata',
    'keywords',
    '',
    'after:alternative'
);
