<?php
if (!defined('TYPO3_MODE')) { die ('Access denied.'); }

$configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ameos_filemanager']);

// plugin
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin('Ameos.' . $_EXTKEY, 'fe_filemanager_explorer',
    [
        'Explorer\\Explorer'   => 'index, search, updateDisplayMode, errors',
        'Explorer\\File'       => 'edit, download, info, upload, remove',
        'Explorer\\Folder'     => 'edit, download, info, remove',
        'Explorer\\Massaction' => 'index',
    ],
    [
        'Explorer\\Explorer'   => 'index, search, updateDisplayMode, errors',
        'Explorer\\File'       => 'edit, download, info, upload, remove',
        'Explorer\\Folder'     => 'edit, download, info, remove',
        'Explorer\\Massaction' => 'index',
    ]
);

// xclass
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Filelist\FileList::class] = 
    ['className' => \Ameos\AmeosFilemanager\XClass\FileList::class];

// Hook to show PluginInformation under a tt_content element
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']
    ['ameos_filemanager'] = \Ameos\AmeosFilemanager\Hooks\PluginPreview::class;
