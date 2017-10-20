<?php
if (!defined('TYPO3_MODE')) { die ('Access denied.'); }

$configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ameos_filemanager']);

// register plugin
if ($configuration['enable_old_plugin']) {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin('Ameos.' . $_EXTKEY, 'fe_filemanager',
        'LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_be.xlf:plugin.fe_filemanager.title');
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin('Ameos.' . $_EXTKEY, 'fe_filemanager_export',
        'LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_be.xlf:plugin.fe_filemanager_export.title');
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin('Ameos.' . $_EXTKEY, 'fe_filemanager_search',
        'LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_be.xlf:plugin.fe_filemanager_search.title');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin('Ameos.' . $_EXTKEY, 'fe_filemanager_explorer',
    'LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_be.xlf:plugin.fe_filemanager_explorer.title');
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin('Ameos.' . $_EXTKEY, 'fe_filemanager_flat',
        'LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_be.xlf:plugin.fe_filemanager_flat.title');

//Flexforms
if ($configuration['enable_old_plugin']) {
    $TCA['tt_content']['types']['list']['subtypes_excludelist']['ameosfilemanager_fe_filemanager'] = 'layout,select_key,recursive';
    $TCA['tt_content']['types']['list']['subtypes_addlist']['ameosfilemanager_fe_filemanager']     = 'pi_flexform';
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('ameosfilemanager_fe_filemanager', 'FILE:EXT:'. $_EXTKEY . '/Configuration/FlexForms/filemanager.xml');

    $TCA['tt_content']['types']['list']['subtypes_excludelist']['ameosfilemanager_fe_filemanager_export'] = 'layout,select_key,recursive';
    $TCA['tt_content']['types']['list']['subtypes_addlist']['ameosfilemanager_fe_filemanager_export']     = 'pi_flexform';
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('ameosfilemanager_fe_filemanager_export', 'FILE:EXT:'. $_EXTKEY . '/Configuration/FlexForms/export.xml');

    $TCA['tt_content']['types']['list']['subtypes_excludelist']['ameosfilemanager_fe_filemanager_search'] = 'layout,select_key,recursive';
    $TCA['tt_content']['types']['list']['subtypes_addlist']['ameosfilemanager_fe_filemanager_search']     = 'pi_flexform';
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('ameosfilemanager_fe_filemanager_search', 'FILE:EXT:'. $_EXTKEY . '/Configuration/FlexForms/search.xml');
}

$TCA['tt_content']['types']['list']['subtypes_excludelist']['ameosfilemanager_fe_filemanager_explorer'] = 'layout,select_key,recursive';
$TCA['tt_content']['types']['list']['subtypes_addlist']['ameosfilemanager_fe_filemanager_explorer']     = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('ameosfilemanager_fe_filemanager_explorer', 'FILE:EXT:'. $_EXTKEY . '/Configuration/FlexForms/explorer.xml');

$TCA['tt_content']['types']['list']['subtypes_excludelist']['ameosfilemanager_fe_filemanager_flat'] = 'layout,select_key,recursive';
$TCA['tt_content']['types']['list']['subtypes_addlist']['ameosfilemanager_fe_filemanager_flat']     = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('ameosfilemanager_fe_filemanager_flat', 'FILE:EXT:'. $_EXTKEY . '/Configuration/FlexForms/flatlist.xml');

// Typoscript
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/', 'File manager > Default');
if ($configuration['enable_old_plugin']) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/Ajax/', 'File manager > Activate Ajax Mode (required jquery)');
}

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
        $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1496933853] =
            \Ameos\AmeosFilemanager\ContextMenu\ItemProviders\FileProvider::class;
    } else {
        $GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][] = array(
            'name' => \Ameos\AmeosFilemanager\Hooks\ClickMenuOptions::class
        );        
    }
    
    // \Ameos\AmeosFilemanager\Slots\Slots
    $dispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class)->get(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);

    // Folders slots
    $dispatcher->connect(\TYPO3\CMS\Core\Resource\ResourceStorage::class, 'postFolderRename', \Ameos\AmeosFilemanager\Slots\Slot::class, 'postFolderRename');
    $dispatcher->connect(\TYPO3\CMS\Core\Resource\ResourceStorage::class, 'postFolderAdd',    \Ameos\AmeosFilemanager\Slots\Slot::class, 'postFolderAdd');
    $dispatcher->connect(\TYPO3\CMS\Core\Resource\ResourceStorage::class, 'postFolderMove',   \Ameos\AmeosFilemanager\Slots\Slot::class, 'postFolderMove');
    $dispatcher->connect(\TYPO3\CMS\Core\Resource\ResourceStorage::class, 'postFolderCopy',   \Ameos\AmeosFilemanager\Slots\Slot::class, 'postFolderCopy');
    $dispatcher->connect(\TYPO3\CMS\Core\Resource\ResourceStorage::class, 'postFolderDelete', \Ameos\AmeosFilemanager\Slots\Slot::class, 'postFolderDelete');

    // Files slots
    $dispatcher->connect(\TYPO3\CMS\Core\Resource\ResourceStorage::class, 'postFileAdd',  \Ameos\AmeosFilemanager\Slots\Slot::class, 'postFileAdd');
    $dispatcher->connect(\TYPO3\CMS\Core\Resource\ResourceStorage::class, 'postFileCopy', \Ameos\AmeosFilemanager\Slots\Slot::class, 'postFileCopy');
    $dispatcher->connect(\TYPO3\CMS\Core\Resource\ResourceStorage::class, 'postFileMove', \Ameos\AmeosFilemanager\Slots\Slot::class, 'postFileMove');

        
    // Register backend ajax request
    \TYPO3\CMS\Core\Utility\\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler(
       'Filemanager::getFolderId',
       \Ameos\AmeosFilemanager\Controller\Backend\AjaxController::class . '->getFolderId'
    );

    // Register backend module
    if ($configuration['enable_export_module']) {
        \TYPO3\CMS\Extbase\Utility\\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
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
