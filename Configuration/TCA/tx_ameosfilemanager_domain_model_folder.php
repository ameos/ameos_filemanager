<?php
if (!defined ('TYPO3_MODE'))    die ('Access denied.');


$GLOBALS['TCA']["tx_ameosfilemanager_domain_model_folder"] = Array (
    "ctrl" => Array (
        'title' => 'LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_db.xml:tx_ameosfilemanager_domain_model_folder',
        'label' => 'title', 
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete'            => 'deleted',
        'enablecolumns' => array (
            'disabled' => 'hidden',
            'fe_group' => 'fe_group_read'
        ),
        'hideTable' => true,
        "default_sortby" => "ORDER BY crdate",
        "iconfile" => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('ameos_filemanager')."ext_icon.gif",
        "searchFields" => "title, description, keywords",
        "rootLevel" => 1,
        "security" => array(
            "ignoreRootLevelRestriction" => 1,
            "ignoreWebMountRestriction" => 1,
        ),
        
    ),
    "feInterface" => Array (
        "fe_admin_fieldList" => "title,description,keywords,fe_groups_access,file,folders,",
    )
);
$GLOBALS['TCA']["tx_ameosfilemanager_domain_model_folder"] = array(
    "ctrl" => $GLOBALS['TCA']["tx_ameosfilemanager_domain_model_folder"]["ctrl"],
    "interface" => array(
        "showRecordFieldList" => "title,description,keywords,fe_groups_access,file,folders,"
    ),
    "feInterface" => $GLOBALS['TCA']["tx_ameosfilemanager_domain_model_folder"]["feInterface"],
    "columns" => array(
        'hidden' => array (        
            'exclude' => 1,
            'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config'  => array (
                'type'    => 'check',
                'default' => '0'
            )
        ),
        'crdate' => array(
            "exclude" => 0, 
            "label" => "LLL:EXT:lang/locallang_general.xml:LGL.crdate",
            "config" => array(
                "type" => "input",
            )
        ),
        'tstamp' => array(
            "exclude" => 0, 
            "label" => "LLL:EXT:lang/locallang_general.xml:LGL.tstamp",
            "config" => array(
                "type" => "input",
            )
        ),
        'cruser_id' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.be_user',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingleBox',
                'size' => 5,
                'maxitems' => 1,
                'foreign_table' => 'be_user',
                'foreign_table_where' => 'ORDER BY be_user.uid'
            )
        ),
        'fe_user_id' => array(
            'exclude' => 0,
            'label' => "LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_db.xml:tx_ameosfilemanager_domain_model_folder.fe_user_id",
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingleBox',
                'maxitems' => 1,
                'size' => 10,
                'foreign_table' => 'fe_users',
            )
        ),
        "title" => array(
            "exclude" => 0, 
            "label" => "LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_db.xml:tx_ameosfilemanager_domain_model_folder.title",
            "config" => array(
                "type" => "input",
                "size" => "30",
                "eval" => "trim",
            )
        ),
        "no_read_access" => array(
            "exclude" => 0, 
            "label" => "LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_db.xml:tx_ameosfilemanager_domain_model_folder.no_read_access",
            "config" => array(
                "type" => "check",
                "default" => "0",
            )
        ),
        "no_write_access" => array(
            "exclude" => 0, 
            "label" => "LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_db.xml:tx_ameosfilemanager_domain_model_folder.no_write_access",
            "config" => array(
                "type" => "check",
                "default" => "0",
            )
        ),
        "description" => array(      
            "exclude" => 0,   
            "label" => "LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_db.xml:tx_ameosfilemanager_domain_model_folder.description",     
            "config" => array(
                "type" => "text", 
                "cols" => "15",
                "rows" => "5", 
                "eval" => "trim", 
            )
        ),
        "identifier" => array(      
            "exclude" => 0,   
            "label" => "LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_db.xml:tx_ameosfilemanager_domain_model_folder.identifier",
            "config" => array(
                "type" => "text",
                "cols" => "15",
                "rows" => "5",
                "eval" => "trim", 
            )
        ),
        'storage' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.storage',
            'config' => array(
                'readOnly' => 1,
                'type' => 'select',
                'renderType' => 'selectSingleBox',
                'items' => array(
                    array('', 0)
                ),
                'foreign_table' => 'sys_file_storage',
                'foreign_table_where' => 'ORDER BY sys_file_storage.name',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1
            )
        ),
        "keywords" => array(      
            "exclude" => 0,   
            "label" => "LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_db.xml:tx_ameosfilemanager_domain_model_folder.keywords",     
            "config" => array(
                "type" => "text", 
                "cols" => "15",
                "rows" => "5", 
                "eval" => "trim", 
            )
        ),
        'fe_group_read' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_db.xml:tx_ameosfilemanager_domain_model_folder.fe_group_read',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 5,
                'maxitems' => 20,/*
                'items' => array(
                    array(
                        'LLL:EXT:lang/locallang_general.xlf:LGL.any_login',
                        -2
                    ),
                    array(
                        'LLL:EXT:lang/locallang_general.xlf:LGL.usergroups',
                        '--div--'
                    )
                ),
                'exclusiveKeys' => '-1,-2',*/
                'items' => array(
                        array(
                            'LLL:EXT:lang/locallang_general.xlf:LGL.any_login',
                            -2
                        ),
                        array(
                            'LLL:EXT:lang/locallang_general.xlf:LGL.usergroups',
                            '--div--'
                        )
                    ),
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title'
            )
        ),
        'fe_group_write' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_db.xml:tx_ameosfilemanager_domain_model_folder.fe_group_write',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 5,
                'maxitems' => 20,/*
                'items' => array(
                    array(
                        'LLL:EXT:lang/locallang_general.xlf:LGL.any_login',
                        -2
                    ),
                    array(
                        'LLL:EXT:lang/locallang_general.xlf:LGL.usergroups',
                        '--div--'
                    )
                ),
                'exclusiveKeys' => '-1,-2',*/
                'items' => array(
                        array(
                            'LLL:EXT:lang/locallang_general.xlf:LGL.any_login',
                            -2
                        ),
                        array(
                            'LLL:EXT:lang/locallang_general.xlf:LGL.usergroups',
                            '--div--'
                        )
                    ),
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title'
            )
        ),
        'fe_group_addfile' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_db.xml:tx_ameosfilemanager_domain_model_folder.fe_group_addfile',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 5,
                'maxitems' => 20,
                'items' => array(
                        array(
                            'LLL:EXT:lang/locallang_general.xlf:LGL.any_login',
                            -2
                        ),
                        array(
                            'LLL:EXT:lang/locallang_general.xlf:LGL.usergroups',
                            '--div--'
                        )
                    ),
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title'
            )
        ),
        'fe_group_addfolder' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_db.xml:tx_ameosfilemanager_domain_model_folder.fe_group_addfolder',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 5,
                'maxitems' => 20,
                'items' => array(
                        array(
                            'LLL:EXT:lang/locallang_general.xlf:LGL.any_login',
                            -2
                        ),
                        array(
                            'LLL:EXT:lang/locallang_general.xlf:LGL.usergroups',
                            '--div--'
                        )
                    ),
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title'
            )
        ),
        'folders' => array( 
            'exclude' => 0,
            'label' => "LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_db.xml:tx_ameosfilemanager_domain_model_folder.folders",
            'config' => array(
                'maxitems' => 500,
                'type' => 'inline',
                'foreign_table' => 'tx_ameosfilemanager_domain_model_folder',
                'foreign_field' => 'uid_parent',
                'appearance' => array(
                  'collapseAll' => 1,
                ),
            )
        ),
        
        'uid_parent' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_db.xml:tx_ameosfilemanager_domain_model_folder.uid_parent',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingleBox',
                'size' => 5,
                'maxitems' => 1,
                'foreign_table' => 'tx_ameosfilemanager_domain_model_folder',
                'foreign_table_where' => 'ORDER BY tx_ameosfilemanager_domain_model_folder.title'
            )
        ),
        'files' => array(
            'exclude' => 0,
            'label' => "LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_db.xml:tx_ameosfilemanager_domain_model_folder.files",
            'config' => array(
                'maxitems' => 500,
                'type' => 'inline',
                'foreign_table' => 'sys_file',
                /*
                'foreign_field' => 'tx_ameosfilemanager_domain_model_folder',
                
                'appearance' => array(
                    'collapseAll' => 1,
                        'headerThumbnail' => array(
                            'field' => 'uid_local',
                            'width' => '45',
                            'height' => '45c',
                    ),
                ),
               */
            )
        ),
        'fe_user_id' => array(
            'exclude' => 0,
            'label' => "LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_db.xml:tx_ameosfilemanager_domain_model_file.fe_user_id",
            'config' => array(
                'type' => 'select',
                'maxitems' => 1,
                'items' => array(
                    array(
                        '',
                        0
                    ),
                ),
                'size' => 1,
                'foreign_table' => 'fe_users',
            )
        ),
    ),

    "types" => array(
        "0" => array("showitem" => "description,keywords,fe_user_id,fe_group_read,no_read_access,fe_group_write,no_write_access,fe_group_addfolder,fe_group_addfile")
    ),
    "palettes" => array(
        "1" => array("showitem" => "")
    )
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable(
        'ameos_filemanager',
        'tx_ameosfilemanager_domain_model_folder',
        // Do not use the default field name ("categories"), which is already used
        // Also do not use a field name containing "categories" (see http://forum.typo3.org/index.php/t/199595/)
        'cats',
        array(
                'exclude' => FALSE,
        )
);
