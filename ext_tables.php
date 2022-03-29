<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$_EXTKEY = 'ameos_filemanager';

$configuration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
    \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
)->get($_EXTKEY);

// Register icons
$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$iconRegistry->registerIcon(
    'extension-ameosfilemanager-main',
    \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
    [
        'source' => 'EXT:' . $_EXTKEY . '/Resources/Public/IconsBackend/folder.svg',
    ]
);

// ContentElementWizard
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:' . $_EXTKEY . '/Configuration/TSConfig/ContentElementWizard.tsconfig">'
);

if (TYPO3_MODE === 'BE') {
    $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1496933853]
        = \Ameos\AmeosFilemanager\ContextMenu\ItemProviders\FileProvider::class;
    // Register backend module
    if ($configuration['enable_export_module']) {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            $_EXTKEY,
            'file',
            'filemanager_export',
            'bottom',
            [
                \Ameos\AmeosFilemanager\Controller\Backend\ExportController::class => 'index, export',
            ],
            [
                'access' => 'user, group',
                'icon'   => 'EXT:' . $_EXTKEY . '/ext_icon.png',
                'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_modexport.xlf',
            ]
        );
    }
}
