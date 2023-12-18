<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die('Access denied');

ExtensionManagementUtility::addStaticFile(
    'ameos_filemanager',
    'Configuration/TypoScript/',
    'File manager > Default'
);
