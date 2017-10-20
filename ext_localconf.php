<?php
if (!defined('TYPO3_MODE')) { die ('Access denied.'); }

$configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ameos_filemanager']);

// plugin
if ($configuration['enable_old_plugin']) {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin('Ameos.' . $_EXTKEY, 'fe_filemanager',
        ['FileManager' => 'index, formFolder, formFile, createFolder, createFile, list, detail, deleteFolder, deleteFile, massDownload'],
        ['FileManager' => 'index, formFolder, formFile, createFolder, createFile, list, detail, deleteFolder, deleteFile, massDownload']
    );
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin('Ameos.' . $_EXTKEY, 'fe_filemanager_export',
        ['Export' => 'index, exportDownloads'],
        ['Export' => 'index, exportDownloads']
    );
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin('Ameos.' . $_EXTKEY, 'fe_filemanager_search',
        ['Search' => 'index'],
        ['Search' => 'index']
    );
}
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin('Ameos.' . $_EXTKEY, 'fe_filemanager_explorer',
    [
        'Explorer\\Explorer' => 'index, search, updateDisplayMode, errors',
        'Explorer\\File'     => 'edit, download, info, upload, remove',
        'Explorer\\Folder'   => 'edit, download, info, remove',
    ],
    [
        'Explorer\\Explorer' => 'index, search, updateDisplayMode, errors',
        'Explorer\\File'     => 'edit, download, info, upload, remove',
        'Explorer\\Folder'   => 'edit, download, info, remove',
    ]
);
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin('Ameos.' . $_EXTKEY, 'fe_filemanager_flat',
    ['FlatList' => 'index', 'FileManager' => 'list, detail, deleteFile, formFile, createFile'],
    ['FlatList' => 'index', 'FileManager' => 'list, detail, deleteFile, formFile, createFile']
);

// xclass
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Filelist\FileList::class] = ['className' => \Ameos\AmeosFilemanager\XClass\FileList::class];

// hooks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][\Ameos\AmeosFilemanager\Hooks\ProcessDatamap::class] =
    'EXT:ameos_filemanager/Classes/Hooks/\Ameos\AmeosFilemanager\Hooks\ProcessDatamap.php:' . \Ameos\AmeosFilemanager\Hooks\ProcessDatamap::class;

// scheduler
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Ameos\AmeosFilemanager\ScheduledTasks\CacheStatus::class] = [
    'extension'        => $_EXTKEY,
    'title'            => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_be.xlf:scheduler.cachestatus.title',
    'description'      => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_be.xlf:scheduler.cachestatus.description',
];

// Hook to show PluginInformation under a tt_content element
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['ameos_filemanager'] =
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('ameos_filemanager') . 'Classes/Hooks/\Ameos\AmeosFilemanager\Hooks\PluginPreview.php:' . \Ameos\AmeosFilemanager\Hooks\PluginPreview::class;

