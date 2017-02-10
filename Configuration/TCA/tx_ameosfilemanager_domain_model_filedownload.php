<?php
if (!defined ('TYPO3_MODE'))    die ('Access denied.');

$GLOBALS['TCA']["tx_ameosfilemanager_domain_model_filedownload"] = Array (
    "ctrl" => Array (
        'title' => 'LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_db.xml:tx_ameosfilemanager_domain_model_filedownload',
        'label' => 'file', 
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete'            => 'deleted',
        'enablecolumns'     => array (
            'disabled' => 'hidden'
        ),
        'hideTable' => true,
        "default_sortby" => "ORDER BY crdate",
        "iconfile" => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('ameos_filemanager')."ext_icon.gif",
        "searchFields" => "file",
    ),
    "feInterface" => Array (
        "fe_admin_fieldList" => "file,crdate,cruser_id,",
    )
);

$GLOBALS['TCA']["tx_ameosfilemanager_domain_model_filedownload"] = array(
    "ctrl" => $GLOBALS['TCA']["tx_ameosfilemanager_domain_model_filedownload"]["ctrl"],
    "interface" => array(
        "showRecordFieldList" => "file,crdate,cruser_id,"
    ),
    "feInterface" => $GLOBALS['TCA']["tx_ameosfilemanager_domain_model_filedownload"]["feInterface"],
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
        'user_download' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.be_user',
            'config' => array(
                'type' => 'select',
                'size' => 5,
                'maxitems' => 1,
                'foreign_table' => 'be_user',
                'foreign_table_where' => 'ORDER BY be_user.uid'
            )
        ),
        'file' => array(
            'exclude' => 0,
            'label' => "LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_db.xml:tx_ameosfilemanager_domain_model_folder.file",
            'config' => array(
                'type' => 'select',
                'foreign_table' => 'sys_file_reference',
                'foreign_table_where' => 'ORDER BY sys_file_reference.title'
            )
        )
    ),

    "types" => array(
        "0" => array("showitem" => "file,crdate,cruser_id,")
    ),
    "palettes" => array(
        "1" => array("showitem" => "")
    )
);
