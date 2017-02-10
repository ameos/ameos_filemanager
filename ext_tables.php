<?php
if (!defined('TYPO3_MODE')) { die ('Access denied.'); }

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

// register plugin
ExtensionUtility::registerPlugin('Ameos.' . $_EXTKEY, 'fe_filemanager', 'Frontend File Manager');
ExtensionUtility::registerPlugin('Ameos.' . $_EXTKEY, 'fe_filemanager_export', 'Frontend File Manager - Export plugin');
ExtensionUtility::registerPlugin('Ameos.' . $_EXTKEY, 'fe_filemanager_search', 'Frontend File Manager - Search form plugin');

//Flexform
$TCA['tt_content']['types']['list']['subtypes_excludelist']['ameosfilemanager_fe_filemanager'] = 'layout,select_key,recursive';
$TCA['tt_content']['types']['list']['subtypes_addlist']['ameosfilemanager_fe_filemanager']     = 'pi_flexform';
ExtensionManagementUtility::addPiFlexFormValue('ameosfilemanager_fe_filemanager', 'FILE:EXT:'. $_EXTKEY . '/Configuration/FlexForms/filemanager.xml');

$TCA['tt_content']['types']['list']['subtypes_excludelist']['ameosfilemanager_fe_filemanager_export'] = 'layout,select_key,recursive';
$TCA['tt_content']['types']['list']['subtypes_addlist']['ameosfilemanager_fe_filemanager_export']     = 'pi_flexform';
ExtensionManagementUtility::addPiFlexFormValue('ameosfilemanager_fe_filemanager_export', 'FILE:EXT:'. $_EXTKEY . '/Configuration/FlexForms/export.xml');

$TCA['tt_content']['types']['list']['subtypes_excludelist']['ameosfilemanager_fe_filemanager_search'] = 'layout,select_key,recursive';
$TCA['tt_content']['types']['list']['subtypes_addlist']['ameosfilemanager_fe_filemanager_search']     = 'pi_flexform';
ExtensionManagementUtility::addPiFlexFormValue('ameosfilemanager_fe_filemanager_search', 'FILE:EXT:'. $_EXTKEY . '/Configuration/FlexForms/search.xml');

// Typoscript
ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/Typoscript/', 'Ameos file manager');

if (TYPO3_MODE == 'BE') {
    // wizicon
    $TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['Ameos\\AmeosFilemanager\\Wizicon\\Filemanager'] = ExtensionManagementUtility::extPath($_EXTKEY) . 'Classes/Wizicon/Filemanager.php';

    //Slots
    $dispatcher = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager')->get('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher');
        
    /*
     * FOLDERS SLOTS
     */
    $dispatcher->connect('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', 'postFolderRename', 'Ameos\\AmeosFilemanager\\Slots\\Slot', 'postFolderRename');
    $dispatcher->connect('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', 'postFolderAdd',    'Ameos\\AmeosFilemanager\\Slots\\Slot', 'postFolderAdd');
    $dispatcher->connect('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', 'postFolderMove',   'Ameos\\AmeosFilemanager\\Slots\\Slot', 'postFolderMove');
    $dispatcher->connect('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', 'postFolderCopy',   'Ameos\\AmeosFilemanager\\Slots\\Slot', 'postFolderCopy');
    $dispatcher->connect('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', 'postFolderDelete', 'Ameos\\AmeosFilemanager\\Slots\\Slot', 'postFolderDelete');

    /*
     * FILES SLOTS
     */
    $dispatcher->connect('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', 'postFileAdd',  'Ameos\\AmeosFilemanager\\Slots\\Slot', 'postFileAdd');
    $dispatcher->connect('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', 'postFileCopy', 'Ameos\\AmeosFilemanager\\Slots\\Slot', 'postFileCopy');
    $dispatcher->connect('TYPO3\\CMS\\Core\\Resource\\ResourceStorage', 'postFileMove', 'Ameos\\AmeosFilemanager\\Slots\\Slot', 'postFileMove');
}
