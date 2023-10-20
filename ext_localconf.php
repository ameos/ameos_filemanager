<?php

use Ameos\AmeosFilemanager\Controller\Explorer\ExplorerController;
use Ameos\AmeosFilemanager\Controller\Explorer\FileController;
use Ameos\AmeosFilemanager\Controller\Explorer\FolderController;
use Ameos\AmeosFilemanager\Controller\Explorer\MassactionController;
use Ameos\AmeosFilemanager\Domain\Model\Category as XClassCategory;
use Ameos\AmeosFilemanager\XClass\FileList as XClassFileList;
use TYPO3\CMS\Extbase\Domain\Model\Category;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;
use TYPO3\CMS\Filelist\FileList;

defined('TYPO3') or die('Access denied');

// plugin
ExtensionUtility::configurePlugin(
    'AmeosFilemanager',
    'FeFilemanagerExplorer',
    [
        ExplorerController::class => 'index, search, updateDisplayMode, errors',
        FileController::class => 'edit, download, info, upload, remove',
        FolderController::class => 'edit, download, info, remove',
        MassactionController::class => 'index',
    ],
    [
        ExplorerController::class => 'index, search, updateDisplayMode, errors',
        FileController::class => 'edit, download, info, upload, remove',
        FolderController::class => 'edit, download, info, remove',
        MassactionController::class => 'index',
    ]
);

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][FileList::class] = ['className' => XClassFileList::class];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][Category::class] = ['className' => XClassCategory::class];

