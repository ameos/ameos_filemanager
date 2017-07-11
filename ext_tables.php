<?php
if (!defined('TYPO3_MODE')) { die ('Access denied.'); }

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use Ameos\AmeosFilemanager\Slots\Slot;

$configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ameos_filemanager']);

// register plugin
if ($configuration['enable_old_plugin']) {
    ExtensionUtility::registerPlugin('Ameos.' . $_EXTKEY, 'fe_filemanager',
        'LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_be.xlf:plugin.fe_filemanager.title');
    ExtensionUtility::registerPlugin('Ameos.' . $_EXTKEY, 'fe_filemanager_export',
        'LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_be.xlf:plugin.fe_filemanager_export.title');
    ExtensionUtility::registerPlugin('Ameos.' . $_EXTKEY, 'fe_filemanager_search',
        'LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_be.xlf:plugin.fe_filemanager_search.title');
}

ExtensionUtility::registerPlugin('Ameos.' . $_EXTKEY, 'fe_filemanager_explorer',
    'LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_be.xlf:plugin.fe_filemanager_explorer.title');
ExtensionUtility::registerPlugin('Ameos.' . $_EXTKEY, 'fe_filemanager_flat',
        'LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_be.xlf:plugin.fe_filemanager_flat.title');

//Flexforms
if ($configuration['enable_old_plugin']) {
    $TCA['tt_content']['types']['list']['subtypes_excludelist']['ameosfilemanager_fe_filemanager'] = 'layout,select_key,recursive';
    $TCA['tt_content']['types']['list']['subtypes_addlist']['ameosfilemanager_fe_filemanager']     = 'pi_flexform';
    ExtensionManagementUtility::addPiFlexFormValue('ameosfilemanager_fe_filemanager', 'FILE:EXT:'. $_EXTKEY . '/Configuration/FlexForms/filemanager.xml');

    $TCA['tt_content']['types']['list']['subtypes_excludelist']['ameosfilemanager_fe_filemanager_export'] = 'layout,select_key,recursive';
    $TCA['tt_content']['types']['list']['subtypes_addlist']['ameosfilemanager_fe_filemanager_export']     = 'pi_flexform';
    ExtensionManagementUtility::addPiFlexFormValue('ameosfilemanager_fe_filemanager_export', 'FILE:EXT:'. $_EXTKEY . '/Configuration/FlexForms/export.xml');

    $TCA['tt_content']['types']['list']['subtypes_excludelist']['ameosfilemanager_fe_filemanager_search'] = 'layout,select_key,recursive';
    $TCA['tt_content']['types']['list']['subtypes_addlist']['ameosfilemanager_fe_filemanager_search']     = 'pi_flexform';
    ExtensionManagementUtility::addPiFlexFormValue('ameosfilemanager_fe_filemanager_search', 'FILE:EXT:'. $_EXTKEY . '/Configuration/FlexForms/search.xml');
}

$TCA['tt_content']['types']['list']['subtypes_excludelist']['ameosfilemanager_fe_filemanager_explorer'] = 'layout,select_key,recursive';
$TCA['tt_content']['types']['list']['subtypes_addlist']['ameosfilemanager_fe_filemanager_explorer']     = 'pi_flexform';
ExtensionManagementUtility::addPiFlexFormValue('ameosfilemanager_fe_filemanager_explorer', 'FILE:EXT:'. $_EXTKEY . '/Configuration/FlexForms/explorer.xml');

$TCA['tt_content']['types']['list']['subtypes_excludelist']['ameosfilemanager_fe_filemanager_flat'] = 'layout,select_key,recursive';
$TCA['tt_content']['types']['list']['subtypes_addlist']['ameosfilemanager_fe_filemanager_flat']     = 'pi_flexform';
ExtensionManagementUtility::addPiFlexFormValue('ameosfilemanager_fe_filemanager_flat', 'FILE:EXT:'. $_EXTKEY . '/Configuration/FlexForms/flatlist.xml');

// Typoscript
ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/', 'File manager > Default');
if ($configuration['enable_old_plugin']) {
    ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/Ajax/', 'File manager > Activate Ajax Mode (required jquery)');
}

// Register icons
$iconRegistry = GeneralUtility::makeInstance(IconRegistry::class);
$iconRegistry->registerIcon('extension-ameosfilemanager-main', SvgIconProvider::class, [
    'source' => 'EXT:ameos_filemanager/Resources/Public/IconsBackend/folder.svg'
]);

/**
 * ContentElementWizard
 */
ExtensionManagementUtility::addPageTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:ameos_filemanager/Configuration/TSConfig/ContentElementWizard.tsconfig">'
);

if (TYPO3_MODE == 'BE') {
    
    if (version_compare(TYPO3_version, '8.0', '>=')) {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1496933853] =
            \Ameos\AmeosFilemanager\ContextMenu\ItemProviders\FileProvider::class;
    } else {
        $GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][] = array(
            'name' => \Ameos\AmeosFilemanager\Hooks\ClickMenuOptions::class
        );        
    }
    
    // Slots
    $dispatcher = GeneralUtility::makeInstance(ObjectManager::class)->get(Dispatcher::class);

    // Folders slots
    $dispatcher->connect(ResourceStorage::class, 'postFolderRename', Slot::class, 'postFolderRename');
    $dispatcher->connect(ResourceStorage::class, 'postFolderAdd',    Slot::class, 'postFolderAdd');
    $dispatcher->connect(ResourceStorage::class, 'postFolderMove',   Slot::class, 'postFolderMove');
    $dispatcher->connect(ResourceStorage::class, 'postFolderCopy',   Slot::class, 'postFolderCopy');
    $dispatcher->connect(ResourceStorage::class, 'postFolderDelete', Slot::class, 'postFolderDelete');

    // Files slots
    $dispatcher->connect(ResourceStorage::class, 'postFileAdd',  Slot::class, 'postFileAdd');
    $dispatcher->connect(ResourceStorage::class, 'postFileCopy', Slot::class, 'postFileCopy');
    $dispatcher->connect(ResourceStorage::class, 'postFileMove', Slot::class, 'postFileMove');

        
    // Register backend ajax request
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler(
       'Filemanager::getFolderId',
       \Ameos\AmeosFilemanager\Controller\Backend\AjaxController::class . '->getFolderId'
    );

    // Register backend module
    if ($configuration['enable_export_module']) {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            'Ameos.' . $_EXTKEY,
            'file',
            'filemanager_export',
            'bottom',
            ['Backend\\Export' => 'index, export'],
            [
                'access' => 'user, group',
                'icon'   => 'EXT:' . $_EXTKEY . '/ext_icon.png',
                'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_modexport.xlf'
            ]
        );
    }
    
}
