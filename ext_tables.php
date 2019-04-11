<?php
if (!defined('TYPO3_MODE')) { die ('Access denied.'); }

$configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ameos_filemanager']);

// Register icons
$iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
$iconRegistry->registerIcon('extension-ameosfilemanager-main', \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class, [
    'source' => 'EXT:ameos_filemanager/Resources/Public/IconsBackend/folder.svg'
]);

// ContentElementWizard
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
    '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:ameos_filemanager/Configuration/TSConfig/ContentElementWizard.tsconfig">'
);

if (TYPO3_MODE == 'BE') {
    
    $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1496933853] =
        \Ameos\AmeosFilemanager\ContextMenu\ItemProviders\FileProvider::class;
    
    // \Ameos\AmeosFilemanager\Slots\Slots
    $dispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class)
        ->get(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);

    // Folders slots
    $dispatcher->connect(
        \TYPO3\CMS\Core\Resource\ResourceStorage::class, 
        'postFolderRename', 
        \Ameos\AmeosFilemanager\Slots\SlotFolder::class, 
        'rename'
    );
    $dispatcher->connect(
        \TYPO3\CMS\Core\Resource\ResourceStorage::class, 
        'postFolderAdd',    
        \Ameos\AmeosFilemanager\Slots\SlotFolder::class, 
        'add'
    );
    $dispatcher->connect(
        \TYPO3\CMS\Core\Resource\ResourceStorage::class, 
        'postFolderMove',   
        \Ameos\AmeosFilemanager\Slots\SlotFolder::class, 
        'move'
    );
    $dispatcher->connect(
        \TYPO3\CMS\Core\Resource\ResourceStorage::class, 
        'postFolderCopy',   
        \Ameos\AmeosFilemanager\Slots\SlotFolder::class, 
        'copy'
    );
    $dispatcher->connect(
        \TYPO3\CMS\Core\Resource\ResourceStorage::class, 
        'postFolderDelete', 
        \Ameos\AmeosFilemanager\Slots\SlotFolder::class, 
        'delete'
    );

    // Files slots
    $dispatcher->connect(
        \TYPO3\CMS\Core\Resource\ResourceStorage::class, 
        'postFileAdd',  
        \Ameos\AmeosFilemanager\Slots\SlotFile::class, 
        'add'
    );
    $dispatcher->connect(
        \TYPO3\CMS\Core\Resource\ResourceStorage::class, 
        'postFileCopy', 
        \Ameos\AmeosFilemanager\Slots\SlotFile::class, 
        'copy'
    );
    $dispatcher->connect(
        \TYPO3\CMS\Core\Resource\ResourceStorage::class, 
        'postFileMove', 
        \Ameos\AmeosFilemanager\Slots\SlotFile::class, 
        'move'
    );

    // initialization slot
    $dispatcher->connect(
        \TYPO3\CMS\Extensionmanager\Utility\InstallUtility::class,
        'afterExtensionInstall',
        \Ameos\AmeosFilemanager\Slots\Install::class, 
        'execute'
    );

    // Register backend module
    if ($configuration['enable_export_module']) {
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            'Ameos.' . $_EXTKEY,
            'file',
            'filemanager_export',
            'bottom',
            ['Backend\\Export' => 'index, export'],
            [
                'access' => 'user, group',
                'icon'   => 'EXT:' . $_EXTKEY . '/ext_icon.png',
                'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_modexport.xlf'
            ]
        );
    }
    
}
