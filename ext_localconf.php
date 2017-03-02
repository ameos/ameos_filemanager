<?php
if (!defined('TYPO3_MODE')) { die ('Access denied.'); }

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Filelist\FileList;
use Ameos\AmeosFilemanager\Hooks\ProcessDatamap;
use Ameos\AmeosFilemanager\XClass\FileList as XClassFileList;
use Ameos\AmeosFilemanager\ScheduledTasks\CacheStatus;

// plugin
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

// xclass
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][FileList::class] = ['className' => XClassFileList::class];

// hooks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][ProcessDatamap::class] = 'EXT:ameos_filemanager/Classes/Hooks/ProcessDatamap.php:' . ProcessDatamap::class;

// scheduler
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][CacheStatus::class] = [
    'extension'        => $_EXTKEY,
    'title'            => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_be.xlf:scheduler.cachestatus.title',
    'description'      => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_be.xlf:scheduler.cachestatus.description',
];
