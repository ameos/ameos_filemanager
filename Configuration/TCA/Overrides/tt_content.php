<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die('Access denied');

// register plugin
ExtensionUtility::registerPlugin(
    'AmeosFilemanager',
    'FeFilemanagerExplorer',
    'LLL:EXT:ameos_filemanager/Resources/Private/Language/locallang_be.xlf:plugin.FeFilemanagerExplorer.title'
);

// flexforms
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['ameosfilemanager_fefilemanagerexplorer']
    = 'layout,select_key,recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['ameosfilemanager_fefilemanagerexplorer']
    = 'pi_flexform';
ExtensionManagementUtility::addPiFlexFormValue(
    'ameosfilemanager_fefilemanagerexplorer',
    'FILE:EXT:ameos_filemanager/Configuration/FlexForms/Explorer.xml'
);
