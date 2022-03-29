<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::makeCategorizable(
    'ameos_filemanager',
    'tx_ameosfilemanager_domain_model_folder',
    'cats',
    ['exclude' => false]
);
