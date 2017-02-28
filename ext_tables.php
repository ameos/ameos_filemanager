<?php
if (!defined('TYPO3_MODE')) { die ('Access denied.'); }

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use Ameos\AmeosFilemanager\Slots\Slot;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

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
ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/', 'File manager > Default');
ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/Ajax/', 'File manager > Activate Ajax Mode (required jquery)');

if (TYPO3_MODE == 'BE') {
    // wizicon
    $TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']['Ameos\\AmeosFilemanager\\Wizicon\\Filemanager'] = ExtensionManagementUtility::extPath($_EXTKEY) . 'Classes/Wizicon/Filemanager.php';

    //Slots
    $dispatcher = GeneralUtility::makeInstance(ObjectManager::class)->get(Dispatcher::class);
        
    /*
     * FOLDERS SLOTS
     */
    $dispatcher->connect(ResourceStorage::class, 'postFolderRename', Slot::class, 'postFolderRename');
    $dispatcher->connect(ResourceStorage::class, 'postFolderAdd',    Slot::class, 'postFolderAdd');
    $dispatcher->connect(ResourceStorage::class, 'postFolderMove',   Slot::class, 'postFolderMove');
    $dispatcher->connect(ResourceStorage::class, 'postFolderCopy',   Slot::class, 'postFolderCopy');
    $dispatcher->connect(ResourceStorage::class, 'postFolderDelete', Slot::class, 'postFolderDelete');

    /*
     * FILES SLOTS
     */
    $dispatcher->connect(ResourceStorage::class, 'postFileAdd',  Slot::class, 'postFileAdd');
    $dispatcher->connect(ResourceStorage::class, 'postFileCopy', Slot::class, 'postFileCopy');
    $dispatcher->connect(ResourceStorage::class, 'postFileMove', Slot::class, 'postFileMove');
}
