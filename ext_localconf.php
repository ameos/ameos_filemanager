<?php
if (!defined('TYPO3_MODE')) { die ('Access denied.'); }

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Filelist\FileList;
use Ameos\AmeosFilemanager\XClass\FileList as XClassFileList;

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
$TYPO3_CONF_VARS['SYS']['Objects'][FileList::class] = ['className' => XClassFileList::class];

