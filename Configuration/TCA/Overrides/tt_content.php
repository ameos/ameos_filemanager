<?php
if (!defined('TYPO3_MODE')) { die ('Access denied.'); }

// register plugin
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Ameos.ameos_filemanager',
    'fe_filemanager_explorer',
    'LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_be.xlf:plugin.fe_filemanager_explorer.title'
);

// flexforms
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']
    ['ameosfilemanager_fe_filemanager_explorer'] = 'layout,select_key,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']
    ['ameosfilemanager_fe_filemanager_explorer']  = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'ameosfilemanager_fe_filemanager_explorer',
    'FILE:EXT:ameos_filemanager/Configuration/FlexForms/Explorer.xml'
);
