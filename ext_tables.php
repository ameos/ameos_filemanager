<?php
if (!defined('TYPO3_MODE')) { die ('Access denied.'); }

// register plugin
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin('Ameos.' . $_EXTKEY, 'fe_filemanager',
    'LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_be.xlf:plugin.fe_filemanager.title');
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin('Ameos.' . $_EXTKEY, 'fe_filemanager_export',
    'LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_be.xlf:plugin.fe_filemanager_export.title');
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin('Ameos.' . $_EXTKEY, 'fe_filemanager_search',
    'LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_be.xlf:plugin.fe_filemanager_search.title');
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin('Ameos.' . $_EXTKEY, 'fe_filemanager_flat',
    'LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_be.xlf:plugin.fe_filemanager_flat.title');

//Flexforms
$TCA['tt_content']['types']['list']['subtypes_excludelist']['ameosfilemanager_fe_filemanager'] = 'layout,select_key,recursive';
$TCA['tt_content']['types']['list']['subtypes_addlist']['ameosfilemanager_fe_filemanager']     = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('ameosfilemanager_fe_filemanager', 'FILE:EXT:'. $_EXTKEY . '/Configuration/FlexForms/filemanager.xml');

$TCA['tt_content']['types']['list']['subtypes_excludelist']['ameosfilemanager_fe_filemanager_export'] = 'layout,select_key,recursive';
$TCA['tt_content']['types']['list']['subtypes_addlist']['ameosfilemanager_fe_filemanager_export']     = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('ameosfilemanager_fe_filemanager_export', 'FILE:EXT:'. $_EXTKEY . '/Configuration/FlexForms/export.xml');

$TCA['tt_content']['types']['list']['subtypes_excludelist']['ameosfilemanager_fe_filemanager_search'] = 'layout,select_key,recursive';
$TCA['tt_content']['types']['list']['subtypes_addlist']['ameosfilemanager_fe_filemanager_search']     = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('ameosfilemanager_fe_filemanager_search', 'FILE:EXT:'. $_EXTKEY . '/Configuration/FlexForms/search.xml');

$TCA['tt_content']['types']['list']['subtypes_excludelist']['ameosfilemanager_fe_filemanager_flat'] = 'layout,select_key,recursive';
$TCA['tt_content']['types']['list']['subtypes_addlist']['ameosfilemanager_fe_filemanager_flat']     = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('ameosfilemanager_fe_filemanager_flat', 'FILE:EXT:'. $_EXTKEY . '/Configuration/FlexForms/flatlist.xml');

// Typoscript
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/', 'File manager > Default');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/Ajax/', 'File manager > Activate Ajax Mode (required jquery)');

// Register icons
$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$iconRegistry->registerIcon('extension-ameosfilemanager-main', \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class, [
    'source' => 'EXT:ameos_filemanager/Resources/Public/IconsBackend/folder.svg'
]);

/**
 * ContentElementWizard
 */
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:ameos_filemanager/Configuration/TSConfig/ContentElementWizard.tsconfig">'
);

if (TYPO3_MODE == 'BE') {
    
    if (version_compare(TYPO3_version, '8.0', '>=')) {
        $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1496933853] = \Ameos\AmeosFilemanager\ContextMenu\ItemProviders\FileProvider::class;
    } else {
        $GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][] = array(
            'name' => 'Ameos\\AmeosFilemanager\\Hooks\\ClickMenuOptions'
        );        
    }
    
    // \Ameos\AmeosFilemanager\Slots\Slots
    $dispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class)->get(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);

    // Folders \Ameos\AmeosFilemanager\Slots\Slots
    $dispatcher->connect(\TYPO3\CMS\Core\Resource\ResourceStorage::class, 'postFolderRename', \Ameos\AmeosFilemanager\Slots\Slot::class, 'postFolderRename');
    $dispatcher->connect(\TYPO3\CMS\Core\Resource\ResourceStorage::class, 'postFolderAdd',    \Ameos\AmeosFilemanager\Slots\Slot::class, 'postFolderAdd');
    $dispatcher->connect(\TYPO3\CMS\Core\Resource\ResourceStorage::class, 'postFolderMove',   \Ameos\AmeosFilemanager\Slots\Slot::class, 'postFolderMove');
    $dispatcher->connect(\TYPO3\CMS\Core\Resource\ResourceStorage::class, 'postFolderCopy',   \Ameos\AmeosFilemanager\Slots\Slot::class, 'postFolderCopy');
    $dispatcher->connect(\TYPO3\CMS\Core\Resource\ResourceStorage::class, 'postFolderDelete', \Ameos\AmeosFilemanager\Slots\Slot::class, 'postFolderDelete');

    // Files \Ameos\AmeosFilemanager\Slots\Slots
    $dispatcher->connect(\TYPO3\CMS\Core\Resource\ResourceStorage::class, 'postFileAdd',  \Ameos\AmeosFilemanager\Slots\Slot::class, 'postFileAdd');
    $dispatcher->connect(\TYPO3\CMS\Core\Resource\ResourceStorage::class, 'postFileCopy', \Ameos\AmeosFilemanager\Slots\Slot::class, 'postFileCopy');
    $dispatcher->connect(\TYPO3\CMS\Core\Resource\ResourceStorage::class, 'postFileMove', \Ameos\AmeosFilemanager\Slots\Slot::class, 'postFileMove');
}
