<?php
if (!defined('TYPO3_MODE')) { die ('Access denied.'); }

$configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ameos_filemanager']);

// Typoscript
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('ameos_filemanager', 'Configuration/TypoScript/', 'File manager > Default');
if ($configuration['enable_old_plugin']) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('ameos_filemanager', 'Configuration/TypoScript/Ajax/', 'File manager > Activate Ajax Mode (required jquery)');
}
