<?php
if (!defined('TYPO3_MODE')) { die ('Access denied.'); }

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Filelist\FileList;
use Ameos\AmeosFilemanager\Hooks\ProcessDatamap;
use Ameos\AmeosFilemanager\XClass\FileList as XClassFileList;
use Ameos\AmeosFilemanager\ScheduledTasks\CacheStatus;
use Ameos\AmeosFilemanager\Hooks\PluginPreview;


$configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ameos_filemanager']);

// plugin
if ($configuration['enable_old_plugin']) {
    ExtensionUtility::configurePlugin('Ameos.' . $_EXTKEY, 'fe_filemanager',
        ['FileManager' => 'index, formFolder, formFile, createFolder, createFile, list, detail, deleteFolder, deleteFile, massDownload'],
        ['FileManager' => 'index, formFolder, formFile, createFolder, createFile, list, detail, deleteFolder, deleteFile, massDownload']
    );
    ExtensionUtility::configurePlugin('Ameos.' . $_EXTKEY, 'fe_filemanager_export',
        ['Export' => 'index, exportDownloads'],
        ['Export' => 'index, exportDownloads']
    );
    ExtensionUtility::configurePlugin('Ameos.' . $_EXTKEY, 'fe_filemanager_search',
        ['Search' => 'index'],
        ['Search' => 'index']
    );
}
ExtensionUtility::configurePlugin('Ameos.' . $_EXTKEY, 'fe_filemanager_explorer',
    [
        'Explorer\\Explorer' => 'index, search, updateDisplayMode, errors',
        'Explorer\\File'     => 'edit, download, info, upload, remove',
        'Explorer\\Folder'   => 'edit, download, remove',
    ],
    [
        'Explorer\\Explorer' => 'index, search, updateDisplayMode, errors',
        'Explorer\\File'     => 'edit, download, info, upload, remove',
        'Explorer\\Folder'   => 'edit, download, remove',
    ]
);
ExtensionUtility::configurePlugin('Ameos.' . $_EXTKEY, 'fe_filemanager_flat',
    ['FlatList' => 'index', 'FileManager' => 'list, detail, deleteFile, formFile, createFile'],
    ['FlatList' => 'index', 'FileManager' => 'list, detail, deleteFile, formFile, createFile']
);

// xclass
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][FileList::class] = ['className' => XClassFileList::class];

// hooks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][ProcessDatamap::class] =
    'EXT:ameos_filemanager/Classes/Hooks/ProcessDatamap.php:' . ProcessDatamap::class;

// scheduler
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][CacheStatus::class] = [
    'extension'        => $_EXTKEY,
    'title'            => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_be.xlf:scheduler.cachestatus.title',
    'description'      => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_be.xlf:scheduler.cachestatus.description',
];

// Hook to show PluginInformation under a tt_content element
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['ameos_filemanager'] =
    ExtensionManagementUtility::extPath('ameos_filemanager') . 'Classes/Hooks/PluginPreview.php:' . PluginPreview::class;

