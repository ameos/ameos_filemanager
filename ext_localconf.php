<?php
if (!defined('TYPO3_MODE')) { die ('Access denied.'); }

// plugin
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin('AmeosFilemanager', 'fe_filemanager_explorer',
    [
        \Ameos\AmeosFilemanager\Controller\Explorer\ExplorerController::class   => 'index, search, updateDisplayMode, errors',
        \Ameos\AmeosFilemanager\Controller\Explorer\FileController::class       => 'edit, download, info, upload, remove',
        \Ameos\AmeosFilemanager\Controller\Explorer\FolderController::class     => 'edit, download, info, remove',
        \Ameos\AmeosFilemanager\Controller\Explorer\MassactionController::class => 'index',
    ],
    [
        \Ameos\AmeosFilemanager\Controller\Explorer\ExplorerController::class   => 'index, search, updateDisplayMode, errors',
        \Ameos\AmeosFilemanager\Controller\Explorer\FileController::class       => 'edit, download, info, upload, remove',
        \Ameos\AmeosFilemanager\Controller\Explorer\FolderController::class     => 'edit, download, info, remove',
        \Ameos\AmeosFilemanager\Controller\Explorer\MassactionController::class => 'index',
    ]
);

// xclass
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Filelist\FileList::class] =
    ['className' => \Ameos\AmeosFilemanager\XClass\FileList::class];

// Hook to show PluginInformation under a tt_content element
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']
    ['ameos_filemanager'] = \Ameos\AmeosFilemanager\Hooks\PluginPreview::class;
