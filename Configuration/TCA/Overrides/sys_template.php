<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$configuration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
    \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
)->get('ameos_filemanager');

// Typoscript
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'ameos_filemanager',
    'Configuration/TypoScript/',
    'File manager > Default'
);

if ($configuration['enable_old_plugin']) {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        'ameos_filemanager',
        'Configuration/TypoScript/Ajax/',
        'File manager > Activate Ajax Mode (required jquery)'
    );
}
